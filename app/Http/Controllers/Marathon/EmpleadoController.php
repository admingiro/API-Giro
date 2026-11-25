<?php

namespace App\Http\Controllers\Marathon;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Request;
use App\Models\EmpFiscal;
use App\Models\EmpPrin;
use App\Models\EmpRh;
use App\Models\EmpSdo;
use App\Models\EmpExt;
use App\Models\EmpJefe;
use App\Models\EmpTipoEmp;
use App\Models\Factor;
use App\Models\RfcEmp;
use Exception;

class EmpleadoController extends Controller
{
    const FECHASALIDA = "2099-12-31";
    const CORREOS_AVISO = "karina.briones@marathonelectric.com;Alexandre@weg.net;soporte@giro.com.mx;desarrollo@giro.com.mx";

    public function __construct()
    {
        $this->path = $this->baseActiva();
        \Config::set('database.connections.empresa.database',$this->path);
    }

    private function baseActiva()
    {
        $path = RfcEmp::select('path_trabajo')->where('base_activa',1)->first()->path_trabajo;

        return trim($path);
    }

    private function strmayus($valor)
    {
        return mb_strtoupper(trim($valor),'utf-8');
    }

    public function index(Request $request)
    {
        $data = DB::connection('empresa')->table("Supervisor_giro.Empprin")->orderby("fecha_captura","desc")->first();

        $host = \Config::get('database.connections.sqlsrv.host');
        $database = \Config::get('database.connections.sqlsrv.database');
        $host_empresa = \Config::get('database.connections.empresa.host');
        $database_empresa = \Config::get('database.connections.empresa.database');

        return response()->json([
            'status' => true,
            'host' => $host,
            'database' => $database,
            'host_empresa' => $host_empresa,
            'database_empresa' => $database_empresa,
            'message' => "Empleados encontrados",
            'empleados' => $data,
        ], 200);
    }

    private function escribeEnBitacora($texto, $update = false)
    {
        $bitacora = "bitacora_store.txt";
        if ($update) $bitacora = "bitacora_update.txt";

        $text = now().' - '.$texto;

        Storage::append($bitacora,$text);
    }

    public function guarda(Request $request)
    {
        DB::connection('empresa')->beginTransaction();

        try {
            $this->escribeEnBitacora("Inicia proceso de creación");
            $this->escribeEnBitacora("JSON: ".json_encode($request->employees));

            foreach ($request->employees as $row)
            {
                $msjError = "";
                $valido = $this->camposValidosParaGrabarNuevoEmpleado($msjError, $row);

                if ($valido == false)
                {
                    $this->escribeEnBitacora("Warning: ".$msjError);
                    $this->enviaErroresPorCorreo("nuevo registro","problema de validación",$msjError);

                    return response()->json([
                        'status' => 502,
                        'message' => $msjError
                    ], 502);
                }
                else
                {
                    $claveEmpleado = trim($row['employee_number']);
                    if ($claveEmpleado != "")
                    {
                        $this->insertaRegistroEmpPrin($row);
                        $this->insertaRegistroEmpExt($row);
                        $this->insertaRegistroEmpSdo($row);
                        $this->insertaRegistroEmpTipoEmp($row);
                        $this->insertaRegistroEmpRH($row);
                        $this->insertaRegistroEmpFiscal($row);
                        $this->insertaRegistroEmpJefe($row);
                        $this->insertaRegistroKardexLocalizacion($row,$row['department_code'],"Supervisor_giro.EmpDep");
                        $this->insertaRegistroKardexLocalizacion($row,$row['position_code']  ,"Supervisor_giro.EmpPues");
                        $this->insertaRegistroKardexLocalizacion($row,$row['branch_code']    ,"Supervisor_giro.EmpSuc");
                        $this->insertaRegistroKardexLocalizacion($row,$row['pay_rate']       ,"Supervisor_giro.EmpTipoNom");
                        $this->insertaRegistroKardexLocalizacion($row,$row['shift_code']     ,"Supervisor_giro.EmpTurno");
                        $this->insertaRegistroKardexLocalizacion($row,$row['calendar_code']  ,"Supervisor_giro.EmpCalendario", true);
                        $this->insertaRegistroKardexLocalizacion($row,'01'                   ,"Supervisor_giro.EmpPatronal");
                    }
                }
            }

            DB::connection('empresa')->commit();

            $this->escribeEnBitacora("El proceso se ejecutó con éxito");

            return response()->json([
                'status' => 201,
                'message' => "Records were created successfully"
            ], 200);

        } catch (Exception $e) {
            DB::connection('empresa')->rollBack();

            $this->escribeEnBitacora("Error: ".$e->getMessage());

            $this->enviaErroresPorCorreo("nuevo registro","error",$e->getMessage());
            
            return response()->json([
                'status' => 500,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    protected function camposValidosParaGrabarNuevoEmpleado(&$msjError, $row)
    {
        $clave              = trim($row['employee_number']);
        $rfc                = trim($row['rfc_number']);
        $curp               = trim($row['curp_number']);
        $numeroSeguroSocial = trim($row['social_security_number']);
        $departamento       = trim($row['department_code']);
        $puesto             = trim($row['position_code']);
        $sucursal           = trim($row['branch_code']);
        $turno              = trim($row['shift_code']);
        $calendario         = trim($row['calendar_code']);
        $tipoNomina         = trim($row['pay_rate']);
        $salarioDiario      = trim($row['daily_salary']);
        $supervisor         = trim($row['ssosupervisor']);

        // campos obligatorios
        if ($rfc  == "") { $msjError = "Employee $clave rfc_number cannot be left empty."; return false; }
        if ($curp == "") { $msjError = "Employee $clave curp_number cannot be left empty."; return false; }
        if ($numeroSeguroSocial        == "") { $msjError = "Employee $clave social_security_number cannot be left empty."; return false; }
        if (trim($row['hire_date'])    == "") { $msjError = "Employee $clave hire_date cannot be left empty."; return false; }
        if (trim($row['age_date_foi']) == "") { $msjError = "Employee $clave age_date_foi cannot be left empty."; return false; }
        
        $this->validaPrestaciones($msjError,$clave,$row['benefits_code']);
        if ($msjError != "") { return false; }

        if ($salarioDiario == "" || $salarioDiario <= 0) { $msjError = "The salary daily of employee $clave must be greater than 0."; return false; }

        if ($departamento == "") { $msjError = "Employee $clave department cannot be left empty."; return false; }
        if ($puesto       == "") { $msjError = "Employee $clave position cannot be left empty."; return false; }
        if ($sucursal     == "") { $msjError = "Employee $clave branch partment cannot be left empty."; return false; }
        if ($tipoNomina   == "") { $msjError = "Employee $clave pay rate cannot be left empty."; return false; }
        if ($turno        == "") { $msjError = "Employee $clave shift cannot be left empty."; return false; }
        if ($calendario   == "") { $msjError = "Employee $clave calendar cannot be left empty."; return false; }
        if ($supervisor   == "") { $msjError = "Employee $clave ssosupervisor cannot be left empty."; return false; }

        // valida datos únicos
        if (EmpPrin::where("clave",$clave)->count() > 0) { $msjError = "The employee number $clave already exists."; return false; }
        if (EmpPrin::where("rfc",$rfc)->count()     > 0) { $msjError = "RFC $rfc of employee $clave already exists."; return false; }
        if (EmpPrin::where("curp",$curp)->count()   > 0) { $msjError = "CURP $curp of employee $clave already exists."; return false; }
        if (EmpPrin::where("afiliacion",$numeroSeguroSocial)->count() > 0) { $msjError = "Social Security Number $numeroSeguroSocial of employee $clave already exists."; return false; }


        // valida que las claves existan en las tablas
        if ($departamento != "" && DB::connection('empresa')->table("Supervisor_giro.Depto")->where("clave",$departamento)->count()  == 0) { $msjError = "Employee $clave department $departamento does not exist."; return false; }
        if ($puesto       != "" && DB::connection('empresa')->table("Supervisor_giro.Puesto")->where("clave",$puesto)->count()       == 0) { $msjError = "Employee $clave position $puesto does not exist."; return false; }
        if ($sucursal     != "" && DB::connection('empresa')->table("Supervisor_giro.Sucursal")->where("clave",$sucursal)->count()   == 0) { $msjError = "Employee $clave branch $sucursal does not exist."; return false; }
        if ($tipoNomina   != "" && DB::connection('empresa')->table("Supervisor_giro.TipoNom")->where("clave",$tipoNomina)->count()  == 0) { $msjError = "Employee $clave pay rate $tipoNomina does not exist."; return false; }
        if ($turno        != "" && DB::connection('empresa')->table("Supervisor_giro.Turno1")->where("clave",$turno)->count()        == 0) { $msjError = "Employee $clave shift $turno does not exist."; return false; }
        if ($calendario   != "" && DB::connection('empresa')->table("Supervisor_giro.Calendar")->where("clave",$calendario)->count() == 0) { $msjError = "Employee $clave calendar $calendario does not exist."; return false; }

        $this->validacionLogitudTipoCampos($msjError,$row);

        if ($msjError != "") return false;

        return true;
    }

    private function validaPrestaciones(&$msjError, $clvEmpleado, $prestacion)
    {
        if (trim($prestacion) == "")
        {
            $msjError = "Employee $clvEmpleado benefits_code cannot be left empty.";
        }
        else
        {
            switch ($prestacion)
            {
                case 'G': $msjError = ""; break;
                case 'B': $msjError = ""; break;
                case '7': $msjError = ""; break;
                case '9': $msjError = ""; break;

                default: $msjError = "Values G, B, 7 or 9 were expected for employee $clvEmpleado"; break;
            }
        }
    }

    public function actualiza(Request $request)
    {
        DB::connection('empresa')->beginTransaction();

        try {
            $this->escribeEnBitacora("Inicia proceso de actualización",true);
            $this->escribeEnBitacora("JSON: ".json_encode($request->employees),true);

            $msjError = "";
            $valido = $this->camposValidosParaActualizar($msjError,$request['employees']);

            if ($valido == false)
            {
                $this->escribeEnBitacora("Warning: ".$msjError,true);
                $this->enviaErroresPorCorreo("actualización","problema de validación",$msjError);

                return response()->json([
                    'status' => 502,
                    'message' => $msjError
                ], 502);
            }
            else
            {
                foreach ($request->employees as $row)
                {
                    $claveEmpleado = trim($row['employee_number']);

                    if ($claveEmpleado != "")
                    {
                        $this->actualizaRegistroEmpPrin($row);
                        $this->actualizaRegistroEmpExt($row);
                        $this->actualizaRegistroEmpSdo($row);
                        $this->actualizaRegistroEmpTipoEmp($row);
                        $this->actualizaRegistroEmpRH($row);
                        $this->actualizaRegistroEmpFiscal($row);

                        $puedeDepto = $this->puedeActualizarCampo("Supervisor_giro.EmpDep", $claveEmpleado);
                        if ($puedeDepto) $this->actualizaRegistroKardexLocalizacion($row,$row['department_code'],"Supervisor_giro.EmpDep");

                        $puedePuesto = $this->puedeActualizarCampo("Supervisor_giro.EmpPues", $claveEmpleado);
                        if ($puedePuesto) $this->actualizaRegistroKardexLocalizacion($row,$row['position_code'],"Supervisor_giro.EmpPues");

                        $puedeSucursal = $this->puedeActualizarCampo("Supervisor_giro.EmpSuc", $claveEmpleado);
                        if ($puedeSucursal) $this->actualizaRegistroKardexLocalizacion($row,$row['branch_code'],"Supervisor_giro.EmpSuc");

                        $puedeTipoNom = $this->puedeActualizarCampo("Supervisor_giro.EmpTipoNom", $claveEmpleado);
                        if ($puedeTipoNom) $this->actualizaRegistroKardexLocalizacion($row,$row['pay_rate'],"Supervisor_giro.EmpTipoNom");

                        $puedeTurno = $this->puedeActualizarCampo("Supervisor_giro.EmpTurno", $claveEmpleado);
                        if ($puedeTurno) $this->actualizaRegistroKardexLocalizacion($row,$row['shift_code'],"Supervisor_giro.EmpTurno");

                        $puedeCalendario = $this->puedeActualizarCampo("Supervisor_giro.EmpCalendario", $claveEmpleado);
                        if ($puedeCalendario) $this->actualizaRegistroKardexLocalizacion($row,$row['calendar_code'],"Supervisor_giro.EmpCalendario");

                        if ($row['ssosupervisor'] != "" && trim($row['date_ssosupervisor']) != "")
                        {
                            $idJefe = $row['ssosupervisor']."-001";
                            $rows = EmpJefe::where("clave",$row['employee_number'])
                                            ->where("fecha_inicio",$this->daFormatoFechaSQL($row['date_ssosupervisor']))
                                            ->where("id_jefe",$idJefe)
                                            ->count();

                            if ($rows == 0) $this->insertaRegistroEmpJefe($row, true);
                        }
                    }
                }

                DB::connection('empresa')->commit();

                $this->escribeEnBitacora("El proceso se ejecutó con éxito",true);

                return response()->json([
                    'status' => 200,
                    'message' => "Records were updated successfully"
                ], 200);
            }

        } catch (Exception $e) {
            DB::connection('empresa')->rollBack();

            $this->escribeEnBitacora("Error: ".$e->getMessage(),true);
            $this->enviaErroresPorCorreo("actualización","error",$e->getMessage());
            
            return response()->json([
                'status' => 500,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    private function camposValidosParaActualizar(&$msjError, $data)
    {
        foreach ($data as $row)
        {
            $this->validacionLogitudTipoCampos($msjError,$row);
            if ($msjError != "") break;

            $clave = $row["employee_number"];
            if (EmpPrin::where("clave",$clave)->count() == 0) { $msjError = "The employee number $clave does not exist."; break; }

            // salario
            $salarioDiario       = trim($row["daily_salary"]);
            $salarioSeguroSocial = trim($row["social_security_salary"]);
            if ($salarioDiario       != "" && $salarioSeguroSocial = "") { $msjError = "The social security salary daily of employee $clave must be greater than 0."; break; }
            if ($salarioSeguroSocial != "" && $salarioDiario = "")       { $msjError = "The salary daily of employee $clave must be greater than 0."; break; }

            $departamento       = trim($row['department_code']);
            $puesto             = trim($row['position_code']);
            $sucursal           = trim($row['branch_code']);
            $turno              = trim($row['shift_code']);
            $tipoNomina         = trim($row['pay_rate']);
            $salarioDiario      = trim($row["daily_salary"]);
            $salarioSeguroSocial= trim($row["social_security_salary"]);

            // valida que las claves existan en las tablas
            if ($departamento != "" && DB::connection('empresa')->table("Supervisor_giro.Depto")->where("clave",$departamento)->count() == 0) { $msjError = "Employee $clave department $departamento does not exist."; break; }
            if ($puesto       != "" && DB::connection('empresa')->table("Supervisor_giro.Puesto")->where("clave",$puesto)->count()      == 0) { $msjError = "Employee $clave position $puesto does not exist."; break; }
            if ($sucursal     != "" && DB::connection('empresa')->table("Supervisor_giro.Sucursal")->where("clave",$sucursal)->count()  == 0) { $msjError = "Employee $clave branch $sucursal does not exist."; break; }
            if ($tipoNomina   != "" && DB::connection('empresa')->table("Supervisor_giro.TipoNom")->where("clave",$tipoNomina)->count() == 0) { $msjError = "Employee $clave pay rate $tipoNomina does not exist."; break; }
            if ($turno        != "" && DB::connection('empresa')->table("Supervisor_giro.Turno1")->where("clave",$turno)->count()       == 0) { $msjError = "Employee $clave shift $turno does not exist."; break; }

            if ($row['ssosupervisor'] != "") if (trim($row['date_ssosupervisor']) == "") { $msjError = "Employee $clave date_ssosupervisor cannot be empty."; break; }
        }

        if ($msjError != "") return false;

        return true;
    }

    private function validacionLogitudTipoCampos(&$msjError, $row)
    {
        // logitud permitida para campos
        if (trim($row['employee_lastname'])        != "") if (strlen(trim($row['employee_lastname']))        > 50)  { $msjError = "Allowed length for employee_lastname is 50";        return false; }
        if (trim($row['employee_mother_lastname']) != "") if (strlen(trim($row['employee_mother_lastname'])) > 50)  { $msjError = "Allowed length for employee_mother_lastname is 50"; return false; }
        if (trim($row['employee_firstname'])       != "") if (strlen(trim($row['employee_firstname']))       > 50)  { $msjError = "Allowed length for employee_firstname is 50";       return false; }
        if (trim($row['rfc_number'])               != "") if (strlen(trim($row['rfc_number']))               > 13)  { $msjError = "Allowed length for rfc_number is 13";               return false; }
        if (trim($row['curp_number'])              != "") if (strlen(trim($row['curp_number']))              > 18)  { $msjError = "Allowed length for curp_number is 18";              return false; }
        if (trim($row['department_code'])          != "") if (strlen(trim($row['department_code']))          > 10)  { $msjError = "Allowed length for department_code is 10";          return false; }
        if (trim($row['position_code'])            != "") if (strlen(trim($row['position_code']))            > 10)  { $msjError = "Allowed length for position_code is 10";            return false; }
        if (trim($row['branch_code'])              != "") if (strlen(trim($row['branch_code']))              > 10)  { $msjError = "Allowed length for branch_code is 10";              return false; }
        if (trim($row['area_code'])                != "") if (strlen(trim($row['area_code']))                > 10)  { $msjError = "Allowed length for area_code is 10";                return false; }
        if (trim($row['linea_code'])               != "") if (strlen(trim($row['linea_code']))               > 10)  { $msjError = "Allowed length for linea_code is 10";               return false; }
        if (trim($row['classification'])           != "") if (strlen(trim($row['classification']))           > 10)  { $msjError = "Allowed length for classification is 10";           return false; }
        if (trim($row['job_category'])             != "") if (strlen(trim($row['job_category']))             > 10)  { $msjError = "Allowed length for job_category is 10";             return false; }
        if (trim($row['calendar_code'])            != "") if (strlen(trim($row['calendar_code']))            > 10)  { $msjError = "Allowed length for calendar_code is 10";            return false; }
        if (trim($row['employee_type'])            != "") if (strlen(trim($row['employee_type']))            > 12)  { $msjError = "Allowed length for employee_type is 12";            return false; }
        if (trim($row['pay_rate'])                 != "") if (strlen(trim($row['pay_rate']))                 > 10)  { $msjError = "Allowed length for pay_rate is 10";                 return false; }
        if (trim($row['pay_regime'])               != "") if (strlen(trim($row['pay_regime']))               > 2)   { $msjError = "Allowed length for pay_regime is 2";                return false; }
        if (trim($row['bank_code'])                != "") if (strlen(trim($row['bank_code']))                > 10)  { $msjError = "Allowed length for bank_code is 10";                return false; }
        if (trim($row['bank_account'])             != "") if (strlen(trim($row['bank_account']))             > 12)  { $msjError = "Allowed length for bank_account is 12";             return false; }
        if (trim($row['shift_code'])               != "") if (strlen(trim($row['shift_code']))               > 10)  { $msjError = "Allowed length for shift_code is 10";               return false; }
        if (trim($row['social_security_number'])   != "") if (strlen(trim($row['social_security_number']))   > 11)  { $msjError = "Allowed length for social_security_number is 11";   return false; }
        if (trim($row['marital_status'])           != "") if (strlen(trim($row['marital_status']))           > 5)   { $msjError = "Allowed length for marital_status is 5";            return false; }
        if (trim($row['gender'])                   != "") if (strlen(trim($row['gender']))                   > 1)   { $msjError = "Allowed length for gender is 1";                    return false; }
        if (trim($row['address_street'])           != "") if (strlen(trim($row['address_street']))           > 30)  { $msjError = "Allowed length for address_street is 30";           return false; }
        if (trim($row['address_exterior_number'])  != "") if (strlen(trim($row['address_exterior_number']))  > 7)   { $msjError = "Allowed length for address_exterior_number is 7";   return false; }
        if (trim($row['address_interior_number'])  != "") if (strlen(trim($row['address_interior_number']))  > 7)   { $msjError = "Allowed length for address_interior_number is 7";   return false; }
        if (trim($row['address_colony'])           != "") if (strlen(trim($row['address_colony']))           > 30)  { $msjError = "Allowed length for address_colony is 30";           return false; }
        if (trim($row['address_town'])             != "") if (strlen(trim($row['address_town']))             > 25)  { $msjError = "Allowed length for address_town is 25";             return false; }
        if (trim($row['address_state'])            != "") if (strlen(trim($row['address_state']))            > 20)  { $msjError = "Allowed length for address_state is 20";            return false; }
        if (trim($row['address_postal_code'])      != "") if (strlen(trim($row['address_postal_code']))      > 5)   { $msjError = "Allowed length for address_postal_code is 5";       return false; }
        if (trim($row['phone_number'])             != "") if (strlen(trim($row['phone_number']))             > 10)  { $msjError = "Allowed length for phone_number is 10";             return false; }
        if (trim($row['cellular_number'])          != "") if (strlen(trim($row['cellular_number']))          > 15)  { $msjError = "Allowed length for cellular_number is 15";          return false; }
        if (trim($row['email'])                    != "") if (strlen(trim($row['email']))                    > 50)  { $msjError = "Allowed length for email is 50";                    return false; }
        if (trim($row['nationality'])              != "") if (strlen(trim($row['nationality']))              > 20)  { $msjError = "Allowed length for nationality is 20";              return false; }
        if (trim($row['birth_date'])               != "") if (strlen(trim($row['birth_date']))               > 25)  { $msjError = "Allowed length for birth_date is 25";               return false; }
        if (trim($row['birth_place_state'])        != "") if (strlen(trim($row['birth_place_state']))        > 20)  { $msjError = "Allowed length for birth_place_state is 20";        return false; }
        if (trim($row['contract_type'])            != "") if (strlen(trim($row['contract_type']))            > 10)  { $msjError = "Allowed length for contract_type is 10";            return false; }
        if (trim($row['fiscal_name'])              != "") if (strlen(trim($row['fiscal_name']))              > 100) { $msjError = "Allowed length for [' is 100";                      return false; }
        if (trim($row['fiscal_postal_code'])       != "") if (strlen(trim($row['fiscal_postal_code']))       > 5)   { $msjError = "Allowed length for fiscal_postal_code is 5";        return false; }
        if (trim($row['ssosupervisor'])            != "") if (strlen(trim($row['ssosupervisor']))            > 10)  { $msjError = "Allowed length for ssosupervisor is 10";            return false; }
        if (trim($row['pay_form'])                 != "") if (strlen(trim($row['pay_form']))                 > 3)   { $msjError = "Allowed length for pay_form is 3";                  return false; }

        // campos tipo bit
        if (trim($row['ptu'])          != "") if ($row['ptu']          != 0 && $row['ptu']          != 1) { $msjError = "Only 1 or 0 allowed for ptu";          return false; }
        if (trim($row['union_worker']) != "") if ($row['union_worker'] != 0 && $row['union_worker'] != 1) { $msjError = "Only 1 or 0 allowed for union_worker"; return false; }

        return true;
    }

    private function puedeActualizarCampo($tabla, $clave)
    {
        $rows = DB::connection('empresa')->table($tabla)->where("clave",$clave)->where("FECHA_ENTRADA",date('Y-m-d'))->count();

        if ($rows > 0) return false;

        return true;
    }

    private function actualizaRegistroKardexLocalizacion($row, $codigoCatalogo, $tabla)
    {
        if ($codigoCatalogo != "")
        {
            DB::connection('empresa')->table($tabla)->insert([
                'CLAVE'         => $row['employee_number'],
                'FECHA_ENTRADA' => date("Y-m-d"),
                'FECHA_SALIDA'  => self::FECHASALIDA,
                'CATALOGO'      => $codigoCatalogo,
                'FECHA_CAPTURA' => now()
            ]);
        }
    }

    private function actualizaRegistroEmpSdo($row)
    {
        if (trim($row['daily_salary']) != "")
        {
            $SalarioIntegrado = $this->daSalarioIntegradoPorModificacion($row['employee_number'],$row['daily_salary'],$row['benefits_code']);

            $EmpSdo = new EmpSdo;
            $EmpSdo->CLAVE         = $row['employee_number'];
            $EmpSdo->FECHA         = date("Y-m-d");
            $EmpSdo->TIPO          = "M";
            $EmpSdo->SDO1          = $row['daily_salary'];
            $EmpSdo->SDO3          = $SalarioIntegrado;
            $EmpSdo->SDO5          = $SalarioIntegrado;
            $EmpSdo->FECHA_CAPTURA = now();
            $EmpSdo->FECHA_SALIDA  = self::FECHASALIDA;
            $EmpSdo->save();
        }
    }

    private function daSalarioIntegradoPorModificacion($empleado, $salario_diario, $prestacion)
    {
        switch ($prestacion)
        {
            case 'G': $clvPrestacion = "03"; break;
            case 'B': $clvPrestacion = "04"; break;
            case '7': $clvPrestacion = "01"; break;
            case '9': $clvPrestacion = "02"; break;

            default: $clvPrestacion = ""; break;
        }

        $fecha = date('Y-m-d');
        $antiguedad = DB::connection('empresa')->select("Select Supervisor_giro.AntiguedadEmpleado('$empleado','$fecha') as antiguedad")[0]->antiguedad;

        $Factor = Factor::select("FACTOR")->where("CLAVE",$clvPrestacion)
                    ->whereraw("$antiguedad >= ANT_INICIAL and $antiguedad <= ANT_FINAL")
                    ->first()->FACTOR;

        $salario_integrado = $salario_diario * $Factor;

        $sdo2 = Empsdo::select('SDO2')->where('CLAVE',$empleado)->where('FECHA_SALIDA','>=','2099-01-01')->first()->SDO2;

        if ($sdo2 > 0) $salario_integrado = $salario_integrado + $sdo2;

        // validación UMAS
        $uma = DB::connection('empresa')->table("Supervisor_giro.Minimo")->orderby("fecha","desc")->first()->UMA;
        $topeSalario = $uma * 25;

        if ($salario_integrado > $topeSalario) $salario_integrado = $topeSalario;


        return round($salario_integrado,2);
    }

    private function actualizaRegistroEmpTipoEmp($row)
    {
        if (trim($row['benefits_code']) != "")
        {
            switch ($row['benefits_code'])
            {
                case 'G': $clvPrestacion = "03"; break;
                case 'B': $clvPrestacion = "04"; break;
                case '7': $clvPrestacion = "01"; break;
                case '9': $clvPrestacion = "02"; break;

                default: $clvPrestacion = ""; break;
            }

            $EmpTipoEmp = new EmpTipoEmp;
            $EmpTipoEmp->CLAVE = $row['employee_number'];
            $EmpTipoEmp->FECHA_ENTRADA = $this->daFormatoFechaSQL($row['hire_date']);
            $EmpTipoEmp->FECHA_CAPTURA = now();
            $EmpTipoEmp->CATALOGO = $clvPrestacion;
            $EmpTipoEmp->save();
        }
    }

    private function actualizaRegistroEmpRH($row)
    {
        $huboCambios = false;

        $EmpRh = EmpRh::where("CLAVE",$row['employee_number'])->first();

        if (trim($row['address_street'])          != "") { $EmpRh->CALLE             = strmayus($row['address_street']);             $huboCambios = true; }
        if (trim($row['address_exterior_number']) != "") { $EmpRh->NUMERO            = strmayus($row['address_exterior_number']);    $huboCambios = true; }
        if (trim($row['address_interior_number']) != "") { $EmpRh->INTERIOR          = strmayus($row['address_interior_number']);    $huboCambios = true; }
        if (trim($row['address_colony'])          != "") { $EmpRh->COLONIA           = strmayus($row['address_colony']);             $huboCambios = true; }
        if (trim($row['address_postal_code'])     != "") { $EmpRh->CP                = $row['address_postal_code'];                  $huboCambios = true; }
        if (trim($row['address_town'])            != "") { $EmpRh->MUNICIPIO         = strmayus($row['address_town']);               $huboCambios = true; }
        if (trim($row['address_state'])           != "") { $EmpRh->ESTADO            = strmayus($row['address_state']);              $huboCambios = true; }
        if (trim($row['phone_number'])            != "") { $EmpRh->TELEFONO_1        = $row['phone_number'];                         $huboCambios = true; }
        if (trim($row['birth_place_city'])        != "") { $EmpRh->LUGAR_NACIMIENTO  = strmayus($row['birth_place_city']);           $huboCambios = true; }
        if (trim($row['marital_status'])          != "") { $EmpRh->ESTADO_CIVIL      = strmayus($row['marital_status']);             $huboCambios = true; }
        if (trim($row['gender'])                  != "") { $EmpRh->SEXO              = strmayus($row['gender']);                     $huboCambios = true; }
        if (trim($row['cellular_number'])         != "") { $EmpRh->TELEFONO_CELULAR  = $row['cellular_number'];                      $huboCambios = true; }
        if (trim($row['birth_place_state'])       != "") { $EmpRh->ESTADO_NACIMIENTO = strmayus($row['birth_place_state']);          $huboCambios = true; }
        if (trim($row['nationality'])             != "") { $EmpRh->NACIONALIDAD      = strmayus($row['nationality']);                $huboCambios = true; }
        if (trim($row['email'])                   != "") { $EmpRh->EMAIL             = $row['email'];                                $huboCambios = true; }
        if (trim($row['birth_date'])              != "") { $EmpRh->FECHA_NACIMIENTO  = $this->daFormatoFechaSQL($row['birth_date']); $huboCambios = true; }

        if ($huboCambios) $EmpRh->save();
    }

    private function actualizaRegistroEmpFiscal($row)
    {
        $huboCambios = false;

        $EmpFiscal = EmpFiscal::find($row['employee_number']);

        if (trim($row['employee_number'])    != "") { $EmpFiscal->CLAVE  = $row['employee_number'];       $huboCambios = true; }
        if (trim($row['fiscal_name'])        != "") { $EmpFiscal->NOMBRE = strmayus($row['fiscal_name']); $huboCambios = true; }
        if (trim($row['fiscal_postal_code']) != "") { $EmpFiscal->CP     = $row['fiscal_postal_code'];    $huboCambios = true; }

        if ($huboCambios) $EmpFiscal->save();
    }

    public function bajaEmpleado(Request $request)
    {
        DB::beginTransaction();

        try {
            $valoresRecibidos = "Values => employee_number: ".$request->employees;
            $valoresRecibidos .= ", termination_date: ".$request->termination_date;
            $valoresRecibidos .= ", cause: ".$request->cause;

            $this->escribeEnBitacora("Inicia proceso de creación", true);
            $this->escribeEnBitacora($valoresRecibidos, true);

            $rows = EmpPrin::where("CLAVE",trim($row['employee_number']))->count();

            if ($rows > 0)
            {
                $EmpSdo = new EmpSdo;
                $EmpSdo->CLAVE        = $request->employee_number;
                $EmpSdo->FECHA        = $this->daFormatoFechaSQL($request->termination_date);
                $EmpSdo->BAJA_IMSS    = $this->daFormatoFechaSQL($request->termination_date);
                $EmpSdo->TIPO         = "B";
                $EmpSdo->CAUSA_BAJA   = $request->cause;
                $EmpSdo->FECHA_SALIDA = self::FECHASALIDA;
                $EmpSdo->save();

                DB::commit();

                $this->escribeEnBitacora("El proceso se ejecutó con éxito",true);

                return response()->json([
                    'status' => 200,
                    'message' => "The employee resigned."
                ], 200);
            }
            else
            {
                $this->escribeEnBitacora("Error, No se encontró el empleado: ".$request->employees,true);

                return response()->json([
                    'status' => 200,
                    'message' => "The employee not found."
                ], 200);
            }

        } catch (Exception $e) {
            DB::rollBack();

            $this->escribeEnBitacora("Error: ".$e->getMessage(), true);

            $this->enviaErroresPorCorreo("baja empleado","error",$e->getMessage());
            
            return response()->json([
                'status' => 500,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function getTiposDeBaja()
    {
        try {
            $data = DB::connection('empresa')->table("Supervisor_giro.Baja")
                    ->select(
                        DB::raw("RTRIM(CLAVE) As id"),
                        DB::raw("RTRIM(Descripcion) As description")
                    )
                    ->get();

            return response()->json([
                'status' => true,
                'data' => $data
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    private function insertaRegistroEmpPrin($row)
    {
        $EmpPrin = new EmpPrin;
        $EmpPrin->CLAVE       = trim($row['employee_number']);
        $EmpPrin->NOMBREN     = strmayus($row['employee_firstname']);
        $EmpPrin->NOMBREP     = strmayus($row['employee_lastname']);
        $EmpPrin->NOMBREM     = strmayus($row['employee_mother_lastname']);
        $EmpPrin->RFC         = strmayus($row['rfc_number']);
        $EmpPrin->CURP        = strmayus($row['curp_number']);
        $EmpPrin->INGRESO     = $this->daFormatoFechaSQL(trim($row['hire_date']));
        $EmpPrin->ACTIVA_JEFE = 0;
        $EmpPrin->ACCESO_WEB  = 0;

        // Tipo empleado
        switch ($row['employee_type'])
        {
            case 'C': $EmpPrin->PE = "E"; break;
            case 'E': $EmpPrin->PE = "E"; break;
            case 'P': $EmpPrin->PE = "P"; break;

            default: $EmpPrin->PE = ""; break;
        }

        // Tipo pago
        // switch ($row['pay_form'])
        // {
        //     case 'C': $EmpPrin->TIPO_PAGO = "CHE"; break;
        //     case 'E': $EmpPrin->TIPO_PAGO = "EFE"; break;
        //     case 'TAR': $EmpPrin->TIPO_PAGO = "TAR"; break;

        //     default: $EmpPrin->TIPO_PAGO = ""; break;
        // }
        $EmpPrin->TIPO_PAGO = "TAR"; // $row['pay_form'];

        if ($row['ptu'] == 1) $EmpPrin->PTU = 1; else $EmpPrin->PTU = 0;
        if ($row['union_worker'] == 1) $EmpPrin->CUOTA_SINDICAL = 1; else $EmpPrin->CUOTA_SINDICAL = 0;

        $EmpPrin->AFILIACION    = $row['social_security_number'];
        $EmpPrin->CTA_CHEQUES   = $row['bank_account'];
        $EmpPrin->CALENDARIO    = $row['calendar_code'];
        $EmpPrin->BANCO         = $row['bank_code'];
        $EmpPrin->TURNOINT      = $row['shift_code'];
        $EmpPrin->REGIMEN       = $row['pay_regime'];
        $EmpPrin->TIPO_CONTRATO = $row['contract_type'];
        $EmpPrin->FECHA_CAPTURA = now();
        $EmpPrin->save();
    }

    private function actualizaRegistroEmpPrin($row)
    {
        $huboCambios = false;

        $EmpPrin = EmpPrin::find($row['employee_number']);

        if (trim($row['employee_firstname'])       != "") { $EmpPrin->NOMBREN = strmayus($row['employee_firstname']);        $huboCambios = true;}
        if (trim($row['employee_lastname'])        != "") { $EmpPrin->NOMBREP = strmayus($row['employee_lastname']);         $huboCambios = true;}
        if (trim($row['employee_mother_lastname']) != "") { $EmpPrin->NOMBREM = strmayus($row['employee_mother_lastname']);  $huboCambios = true;}
        if (trim($row['rfc_number'])               != "") { $EmpPrin->RFC     = strmayus($row['rfc_number']);                $huboCambios = true;}
        if (trim($row['curp_number'])              != "") { $EmpPrin->CURP    = strmayus($row['curp_number']);               $huboCambios = true;}
        if (trim($row['hire_date'])                != "") { $EmpPrin->INGRESO = $this->daFormatoFechaSQL($row['hire_date']); $huboCambios = true;}

        // Tipo empleado
        if (trim($row['employee_type']) != "")
        {
            switch ($row['employee_type'])
            {
                case 'C': $EmpPrin->PE = "E"; break;
                case 'E': $EmpPrin->PE = "E"; break;
                case 'P': $EmpPrin->PE = "P"; break;

                default: $EmpPrin->PE = ""; break;
            }
            $huboCambios = true;
        }

        // Tipo pago
        if (trim($row['pay_form']))
        {
            switch ($row['pay_form'])
            {
                case 'C': $EmpPrin->TIPO_PAGO = "CHE"; break;
                case 'E': $EmpPrin->TIPO_PAGO = "EFE"; break;
                case 'T': $EmpPrin->TIPO_PAGO = "TAR"; break;

                default: $EmpPrin->TIPO_PAGO = ""; break;
            }
            $huboCambios = true;
        }

        if (trim($row['ptu'])          != "") { if ($row['ptu'] == 1) $EmpPrin->PTU = 1; else $EmpPrin->PTU = 0;                                $huboCambios = true; }
        if (trim($row['union_worker']) != "") { if ($row['union_worker'] == 1) $EmpPrin->CUOTA_SINDICAL = 1; else $EmpPrin->CUOTA_SINDICAL = 0; $huboCambios = true; }

        if (trim($row['social_security_number']) != "") { $EmpPrin->AFILIACION    = $row['social_security_number']; $huboCambios = true;}
        if (trim($row['bank_account'])           != "") { $EmpPrin->CTA_CHEQUES   = $row['bank_account'];           $huboCambios = true;}
        if (trim($row['calendar_code'])          != "") { $EmpPrin->CALENDARIO    = $row['calendar_code'];          $huboCambios = true;}
        if (trim($row['bank_code'])              != "") { $EmpPrin->BANCO         = $row['bank_code'];              $huboCambios = true;}
        if (trim($row['shift_code'])             != "") { $EmpPrin->TURNOINT      = $row['shift_code'];             $huboCambios = true;}
        if (trim($row['pay_regime'])             != "") { $EmpPrin->REGIMEN       = $row['pay_regime'];             $huboCambios = true;}
        if (trim($row['contract_type'])          != "") { $EmpPrin->TIPO_CONTRATO = $row['contract_type'];          $huboCambios = true;}

        if ($huboCambios) $EmpPrin->save();
    }

    private function insertaRegistroEmpExt($row)
    {
        $EmpExt = new EmpExt;
        $EmpExt->CLAVE                = $row['employee_number'];
        $EmpExt->AREA                 = strmayus($row['area_code']);
        $EmpExt->LINEA                = strmayus($row['linea_code']);
        $EmpExt->FECHA_FOI            = $this->daFormatoFechaSQL($row['age_date_foi']);
        $EmpExt->JOB_CATEGORY         = strmayus($row['job_category']);
        $EmpExt->CLASIFICACION        = strmayus($row['classification']);
        $EmpExt->NOMBRE_FISCAL        = strmayus($row['fiscal_name']);
        $EmpExt->CODIGO_POSTAL_FISCAL = $row['fiscal_postal_code'];
        $EmpExt->save();
    }

    private function actualizaRegistroEmpExt($row)
    {
        $EmpExt = EmpExt::find($row['employee_number']);

        $huboCambios = false;

        if (trim($row['area_code'])          != "") { $EmpExt->AREA                 = strmayus($row['area_code']);                    $huboCambios = true; }
        if (trim($row['linea_code'])         != "") { $EmpExt->LINEA                = strmayus($row['linea_code']);                   $huboCambios = true; }
        if (trim($row['age_date_foi'])       != "") { $EmpExt->FECHA_FOI            = $this->daFormatoFechaSQL($row['age_date_foi']); $huboCambios = true; }
        if (trim($row['job_category'])       != "") { $EmpExt->JOB_CATEGORY         = strmayus($row['job_category']);                 $huboCambios = true; }
        if (trim($row['classification'])     != "") { $EmpExt->CLASIFICACION        = strmayus($row['classification']);               $huboCambios = true; }
        if (trim($row['fiscal_name'])        != "") { $EmpExt->NOMBRE_FISCAL        = strmayus($row['fiscal_name']);                  $huboCambios = true; }
        if (trim($row['fiscal_postal_code']) != "") { $EmpExt->CODIGO_POSTAL_FISCAL = $row['fiscal_postal_code'];                     $huboCambios = true; }

        if ($huboCambios) $EmpExt->save();
    }

    private function insertaRegistroEmpSdo($row, $tipo = "A")
    {
        $SalarioIntegrado = $this->daSalarioIntegrado($row['employee_number'],$row['daily_salary'],$row['benefits_code']);

        $EmpSdo = new EmpSdo;
        $EmpSdo->CLAVE         = $row['employee_number'];
        $EmpSdo->FECHA         = $this->daFormatoFechaSQL($row['hire_date']);
        $EmpSdo->TIPO          = $tipo;
        $EmpSdo->SDO1          = $row['daily_salary'];
        $EmpSdo->SDO3          = $SalarioIntegrado;
        $EmpSdo->SDO5          = $SalarioIntegrado;
        $EmpSdo->FECHA_CAPTURA = now();
        $EmpSdo->FECHA_SALIDA  = self::FECHASALIDA;
        $EmpSdo->save();
    }

    private function insertaRegistroEmpTipoEmp($row)
    {
        switch ($row['benefits_code'])
        {
            case 'G': $clvPrestacion = "03"; break;
            case 'B': $clvPrestacion = "04"; break;
            case '7': $clvPrestacion = "01"; break;
            case '9': $clvPrestacion = "02"; break;

            default: $clvPrestacion = ""; break;
        }

        $EmpTipoEmp = new EmpTipoEmp;
        $EmpTipoEmp->CLAVE = $row['employee_number'];
        $EmpTipoEmp->FECHA_ENTRADA = $this->daFormatoFechaSQL($row['hire_date']);
        $EmpTipoEmp->FECHA_CAPTURA = now();
        $EmpTipoEmp->CATALOGO = $clvPrestacion;
        $EmpTipoEmp->save();
    }

    private function daSalarioIntegrado($empleado, $salario_diario, $prestacion)
    {
        switch ($prestacion)
        {
            case 'G': $clvPrestacion = "03"; break;
            case 'B': $clvPrestacion = "04"; break;
            case '7': $clvPrestacion = "01"; break;
            case '9': $clvPrestacion = "02"; break;

            default: $clvPrestacion = ""; break;
        }

        $Factor = Factor::where("CLAVE",$clvPrestacion)->first();

        $salario_integrado = $salario_diario * $Factor->FACTOR;

        return round($salario_integrado,2);
    }

    private function insertaRegistroEmpRH($row)
    {
        $EmpRh = new EmpRh;
        $EmpRh->CLAVE             = $row['employee_number'];
        $EmpRh->CALLE             = strmayus($row['address_street']);
        $EmpRh->NUMERO            = strmayus($row['address_exterior_number']);
        $EmpRh->INTERIOR          = strmayus($row['address_interior_number']);
        $EmpRh->COLONIA           = strmayus($row['address_colony']);
        $EmpRh->CP                = $row['address_postal_code'];
        $EmpRh->MUNICIPIO         = strmayus($row['address_town']);
        $EmpRh->ESTADO            = strmayus($row['address_state']);
        $EmpRh->TELEFONO_1        = $row['phone_number'];
        $EmpRh->LUGAR_NACIMIENTO  = strmayus($row['birth_place_city']);
        $EmpRh->ESTADO_CIVIL      = strmayus($row['marital_status']);
        $EmpRh->SEXO              = strmayus($row['gender']);
        $EmpRh->TELEFONO_CELULAR  = $row['cellular_number'];
        $EmpRh->ESTADO_NACIMIENTO = strmayus($row['birth_place_state']);
        $EmpRh->NACIONALIDAD      = strmayus($row['nationality']);
        $EmpRh->EMAIL             = $row['email'];

        if (trim($row['birth_date']) != "") $EmpRh->FECHA_NACIMIENTO = $this->daFormatoFechaSQL($row['birth_date']);

        $EmpRh->save();
    }

    private function insertaRegistroEmpFiscal($row)
    {
        $rows  = EmpFiscal::where("CLAVE",$row['employee_number'])->count();

        if ($rows > 0)
            $EmpFiscal = EmpFiscal::find($row['employee_number']);
        else
            $EmpFiscal = new EmpFiscal;

        $EmpFiscal->CLAVE  = $row['employee_number'];
        $EmpFiscal->NOMBRE = strmayus($row['fiscal_name']);
        $EmpFiscal->CP     = $row['fiscal_postal_code'];
        $EmpFiscal->save();
    }

    private function insertaRegistroEmpJefe($row, $update = false)
    {
        $EmpJefe = new EmpJefe;
        $EmpJefe->CLAVE = $row['employee_number'];

        if ($update)
            $EmpJefe->FECHA_INICIO  = $this->daFormatoFechaSQL($row['date_ssosupervisor']);
        else
            $EmpJefe->FECHA_INICIO  = $this->daFormatoFechaSQL($row['hire_date']);

        $EmpJefe->FECHA_FIN     = self::FECHASALIDA;
        $EmpJefe->ID_JEFE       = $row['ssosupervisor']."-001";
        $EmpJefe->FECHA_CAPTURA = now();
        $EmpJefe->save();
    }

    private function insertaRegistroKardexLocalizacion($row, $codigoCatalogo, $tabla, $calendario = false)
    {
        if (trim($codigoCatalogo) != "")
        {
            if ($calendario)
            {
                DB::connection('empresa')->table($tabla)->where("clave",trim($row['employee_number']))
                ->update([
                    'CATALOGO'      => $codigoCatalogo,
                    'FECHA_CAPTURA' => now()
                ]);
            }
            else
            {
                DB::connection('empresa')->table($tabla)->insert([
                    'CLAVE'         => $row['employee_number'],
                    'FECHA_ENTRADA' => $this->daFormatoFechaSQL($row['hire_date']),
                    'FECHA_SALIDA'  => self::FECHASALIDA,
                    'CATALOGO'      => $codigoCatalogo,
                    'FECHA_CAPTURA' => now()
                ]);
            }
        }
    }

    private function daFormatoFechaSQL($cadenaFecha)
    {
        $exp = explode("/",$cadenaFecha);

        return $exp[2]."-".$exp[1]."-".$exp[0];

        // $strAux = str_replace('/','-',$cadenaFecha);
        // return date("Y-m-d", strtotime($cadenaFecha));
    }

    protected function enviaErroresPorCorreo($proceso, $problema, $msjError)
    {
        $descDias = ['Domingo','Lunes','Martes','Miéroles','Jueves','Viernes','Sábado'];
        $descMeses = ['enero','febrero','marzo','abril','mayo','junio','julio','agosto','septiembre','octubre','noviembre','diciembre'];

        $data['fecha']    = $descDias[date('w')]." ".date('j')." de ".$descMeses[date('n')-1]." de ".date('Y')." a las ".date('H:i:s');
        $data['problema'] = $problema;
        $data['proceso']  = $proceso;
        $data['msjError'] = $msjError;

        $correos = self::CORREOS_AVISO;
        // Mail::send('correos.errores', $data, function ($message) use($correos)   {
        //     $message->from(env('MAIL_FROM_ADDRESS', 'notificaciones@giroweb.com.mx'));
        //     $message->subject('ERROR proceso API Giro-Marathon'); 
        //     $to = explode(";",$correos);
        //     $message->to($to);
        // });
    }

}
