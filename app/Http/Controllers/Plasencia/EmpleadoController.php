<?php

namespace App\Http\Controllers\Plasencia;

use App\Http\Controllers\Controller;
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
use App\Models\EmpEducacion;
use App\Models\Factor;
use App\Models\RfcEmp;
use App\Models\Empleado;
use App\Models\EmpPatronal;
use App\Models\Patronal;
use Exception;

class EmpleadoController extends Controller
{
    const FECHASALIDA = "2099-12-31";

    public function __construct()
    {
        $this->path = $this->baseActiva();
        \Config::set('database.connections.empresa.database',$this->path);
    }

    private function baseActiva()
    {
        $path = RfcEmp::select('path_trabajo')->where('base_api_test',1)->first()->path_trabajo;

        return trim($path);
    }

    public function config()
    {
        echo "Base: ".\Config::get('database.connections.empresa.database');
    }

    public function vigentes(Request $request)
    {
        $data = DB::connection('empresa')->table("Supervisor_giro.Empprin")->orderby("fecha_captura","desc")->first();

        $empleados = Empleado::select(
                            DB::raw("rtrim(Supervisor_giro.vista_empleado.clave)                     As IDEMPLEADO"),
                            DB::raw("rtrim(Supervisor_giro.vista_empleado.nombre)                    As NOMBRE"),
                            DB::raw("rtrim(Supervisor_giro.vista_empleado.apellido_paterno)          As PATERNO"),
                            DB::raw("rtrim(Supervisor_giro.vista_empleado.apellido_materno)          As MATERNO"),
                            DB::raw("'Giro'                                                          As FUENTE"),
                            DB::raw("rtrim(Supervisor_giro.vista_empleado.rfc)                       As RFC"),
                            DB::raw("rtrim(Supervisor_giro.vista_empleado.curp)                      As CURP"),
                            DB::raw("rtrim(Supervisor_giro.vista_empleado.afiliacion)                As NSS"),
                            DB::raw("rtrim(EmpExt.email)                                             As CORREOEMPRESA"),
                            DB::raw("rtrim(Emprh.email)                                              As CORREOPERSONAL"),
                            DB::raw("Convert(varchar, Emprh.fecha_nacimiento, 112)                   As NACIMIENTO"),
                            DB::raw("rtrim(Emprh.sexo)                                               As SEXO"),
                            DB::raw("rtrim(Emprh.estado_civil)                                       As CIVIL"),
                            DB::raw("rtrim(Emprh.telefono_1)                                         As TELEFONO"),
                            DB::raw("''                                                              As EXTENSION"),
                            DB::raw("rtrim(Emprh.telefono_celular)                                   As CELULAR"),
                            DB::raw("Convert(varchar, ingreso, 112)                                  As INGRESO"),
                            DB::raw("Convert(varchar, Emppues.fecha_entrada, 112)                    As FECHAPUESTO"),
                            DB::raw("replace(SUBSTRING(ID_JEFE, 1, CHARINDEX('-', ID_JEFE)),'-','')  As JEFE"),
                            DB::raw("''                                                              As DIRECCION"),
                            DB::raw("rtrim(descripcion_depto)                                        As DEPARTAMENTO"),
                            DB::raw("''                                                              As SECCION"),
                            DB::raw("rtrim(descripcion_puesto)                                       As PUESTO"),
                            DB::raw("''                                                              As GRADO"),
                            DB::raw("''                                                              As REGION"),
                            DB::raw("rtrim(descripcion_sucursal)                                     As SUCURSAL"),
                            DB::raw("rtrim(Patronal.empresa)                                         As IDEMPRESA"),
                            DB::raw("rtrim(Patronal.nombre)                                          As EMPRESA"),
                            DB::raw("''                                                              As DIVISION"),
                            DB::raw("''                                                              As MARCA"),
                            DB::raw("''                                                              As CENTRO"),
                            DB::raw("''                                                              As CHECADOR"),
                            DB::raw("rtrim(turno)                                                    As TURNO"),
                            DB::raw("rtrim(descripciontipo_nom)                                      As TIPONOMINA"),
                            DB::raw("rtrim(tipo_nom)                                                 As CLAVENOMINA"),
                            DB::raw("''                                                              As NOMBRENOMINA"),
                            DB::raw("''                                                              As GENERALISTA"),
                            DB::raw("''                                                              As RELACION"),
                            DB::raw("rtrim(Supervisor_giro.vista_empleado.DescripcionTipo_Contrato)  As CONTRATO"),
                            DB::raw("''                                                              As HORARIO"),
                            DB::raw("''                                                              As JORNADA"),
                            DB::raw("''                                                              As CALCULO"),
                            DB::raw("''                                                              As VACACIONES"),
                            DB::raw("''                                                              As FLOTANTE"),
                            DB::raw("''                                                              As BASE"),
                            DB::raw("''                                                              As ROL"),
                            DB::raw("''                                                              As PASSWORD"),
                            DB::raw("''                                                              As EXTRA1"),
                            DB::raw("''                                                              As EXTRA2"),
                            DB::raw("''                                                              As EXTRA3"),
                            DB::raw("''                                                              As EXTRA4"),
                            DB::raw("''                                                              As EXTRA5"),
                            DB::raw("Convert(varchar,getdate(),112)                                  As FECHA"),
                            DB::raw("'2.15'                                                          As VERSION"),
                            
                        )
                        ->where('vigencia','vigente')
                        ->join("Supervisor_giro.Emprh As Emprh","Supervisor_giro.vista_empleado.clave","Emprh.clave")
                        ->join("Supervisor_giro.EmpExt As EmpExt","Supervisor_giro.vista_empleado.clave","EmpExt.clave")
                        ->leftjoin('Supervisor_giro.Emppues As Emppues', function ($join) {
                            $join->on('Supervisor_giro.vista_empleado.clave','Emppues.clave')
                            ->on('Emppues.fecha_salida','>=',DB::raw("'2099-01-01'"));
                        })
                        ->leftjoin('Supervisor_giro.Emp_Jefe As Emp_Jefe', function ($join) {
                            $join->on('Supervisor_giro.vista_empleado.clave','Emp_Jefe.clave')
                            ->on('Emp_Jefe.fecha_fin','>=',DB::raw("'2099-01-01'"));
                        })
                        ->leftjoin('Supervisor_giro.EmpPatronal As EmpPatronal', function ($join) {
                            $join->on('Supervisor_giro.vista_empleado.clave','EmpPatronal.clave')
                            ->on('EmpPatronal.fecha_salida','>=',DB::raw("'2099-01-01'"));
                        })
                        ->leftjoin('Supervisor_giro.Patronal As Patronal','EmpPatronal.catalogo','Patronal.clave')
                        ->get();

        return response()->json($empleados, 200);
    }

    private function escribeEnBitacora($texto, $update = false)
    {
        $bitacora = "bitacora_store.txt";
        if ($update) $bitacora = "bitacora_update.txt";

        $text = now().' - '.$texto;

        Storage::append($bitacora,$text);
    }

    public function nuevo(Request $request)
    {
        DB::connection('empresa')->beginTransaction();

        try {
            $this->escribeEnBitacora("Inicia proceso de creación");

            $empleado = $request;
            $this->escribeEnBitacora("JSON: ".$empleado);

            $msjError = "";
            $valido = $this->camposValidosParaGrabarNuevoEmpleado($msjError, $empleado);


            if ($valido == false)
            {
                $this->escribeEnBitacora("Warning: ".$msjError);

                return response()->json([
                    'code' => 502,
                    'success' => false,
                    'message' => $msjError
                ], 502);
            }
            else
            {
                $this->insertaRegistroEmpPrin($empleado);
                $this->insertaRegistroEmpExt($empleado);
                $this->insertaRegistroEmpSdo($empleado);
                $this->insertaRegistroEmpTipoEmp($empleado);
                $this->insertaRegistroEmpRH($empleado);
                $this->insertaRegistroEmpFiscal($empleado);
                $this->insertaRegistroEmpJefe($empleado);
                $this->insertaRegistroEmpEducacion($empleado);
                $this->insertaRegistroKardexLocalizacion($empleado,$empleado->departamento     ,"Supervisor_giro.EmpDep");
                $this->insertaRegistroKardexLocalizacion($empleado,$empleado->puesto           ,"Supervisor_giro.EmpPues");
                $this->insertaRegistroKardexLocalizacion($empleado,$empleado->sucursal         ,"Supervisor_giro.EmpSuc");
                $this->insertaRegistroKardexLocalizacion($empleado,$empleado->tipoNomina       ,"Supervisor_giro.EmpTipoNom");
                $this->insertaRegistroKardexLocalizacion($empleado,$empleado->turno            ,"Supervisor_giro.EmpTurno");
                $this->insertaRegistroKardexLocalizacion($empleado,$empleado->calendarioInhabil,"Supervisor_giro.EmpCalendario", true);
                $this->insertaRegistroKardexLocalizacion($empleado,'01'                        ,"Supervisor_giro.EmpPatronal");
            }

            DB::connection('empresa')->commit();

            $this->escribeEnBitacora("El proceso se ejecutó con éxito");

            return response()->json([
                'code' => 201,
                'success' => true,
                'message' => "El empleado se ha registrado con éxito"
            ], 201);

        } catch (Exception $e) {
            DB::connection('empresa')->rollBack();

            $this->escribeEnBitacora("Error: ".$e->getMessage());
            
            return response()->json([
                'code' => 500,
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    protected function camposValidosParaGrabarNuevoEmpleado(&$msjError, $empleado)
    {
        // campos obligatorios
        $campo = "";
        if (trim($empleado->clave)             == "") $campo = "clave";
        if (trim($empleado->rfc)               == "") $campo = "rfc";
        if (trim($empleado->curp)              == "") $campo = "curp";
        if (trim($empleado->afiliacion)        == "") $campo = "afiliacion";
        if (trim($empleado->departamento)      == "") $campo = "departamento";
        if (trim($empleado->puesto)            == "") $campo = "puesto";
        if (trim($empleado->sucursal)          == "") $campo = "sucursal";
        if (trim($empleado->turno)             == "") $campo = "turno";
        if (trim($empleado->calendarioInhabil) == "") $campo = "calendarioInhabil";
        if (trim($empleado->tipoNomina)        == "") $campo = "tipoNomina";
        if (trim($empleado->jefeInmediato)     == "") $campo = "jefeInmediato";
        if (trim($empleado->nombre)            == "") $campo = "nombre";
        if (trim($empleado->apellidoPaterno)   == "") $campo = "apellidoPaterno";
        if (trim($empleado->correoPersonal)    == "") $campo = "correoPersonal";
        if (trim($empleado->regimen)           == "") $campo = "regimen";
        if (trim($empleado->jornada)           == "") $campo = "jornada";
        if (trim($empleado->tipoContrato)      == "") $campo = "tipoContrato";
        if (trim($empleado->tipoEmpleado)      == "") $campo = "tipoEmpleado";
        if (trim($empleado->fechaIngreso)      == "") $campo = "fechaIngreso";
        if (trim($empleado->patronal)          == "") $campo = "patronal";
        if (trim($empleado->fechaNacimiento)   == "") $campo = "fechaNacimiento";
        // if (trim($empleado->semanaReducida)    == "") $campo = "semanaReducida"; // investigar que es
        // if (trim($empleado->jornadaReducida)   == "") $campo = "jornadaReducida"; // investigar que es
        // if (trim($empleado->prestacion)        == "") $campo = "prestacion";

        if ($msjError != "" ) { $msjError = 'El campo '.$campo.' no puede quedar vacío.'; return false; }

        // $this->validaPrestaciones($msjError,$empleado->clave,$empleado->prestacion);

        if (trim($empleado->salarioDiario) == "" || $empleado->salarioDiario <= 0) { $msjError = "El salario debe ser mayor a 0."; return false; }

        // valida datos únicos
        if (EmpPrin::where("clave",$empleado->clave)->count() > 0) { $msjError = "La clave $empleado->clave ya existe."; return false; }
        if (EmpPrin::where("rfc",$empleado->rfc)->count()     > 0) { $msjError = "El RFC $empleado->rfc ya existe."; return false; }
        if (EmpPrin::where("curp",$empleado->curp)->count()   > 0) { $msjError = "El CURP $empleado->curp ya existe."; return false; }
        if (EmpPrin::where("afiliacion",$empleado->afiliacion)->count() > 0) { $msjError = "El número de afiliación $empleado->afiliacion ya existe."; return false; }


        // valida que las claves existan en las tablas
        if (DB::connection('empresa')->table("Supervisor_giro.Depto")->where("clave",$empleado->departamento)->count()  == 0) { $msjError = "El departamento $empleado->departamento no existe."; return false; }
        if (DB::connection('empresa')->table("Supervisor_giro.Puesto")->where("clave",$empleado->puesto)->count()       == 0) { $msjError = "El puesto $empleado->puesto no existe."; return false; }
        if (DB::connection('empresa')->table("Supervisor_giro.Sucursal")->where("clave",$empleado->sucursal)->count()   == 0) { $msjError = "La sucursal $empleado->sucursal no existe."; return false; }
        if (DB::connection('empresa')->table("Supervisor_giro.TipoNom")->where("clave",$empleado->tipoNomina)->count()  == 0) { $msjError = "El tipo de nómina $empleado->tipoNomina no existe."; return false; }
        if (DB::connection('empresa')->table("Supervisor_giro.Turno1")->where("clave",$empleado->turno)->count()        == 0) { $msjError = "El turno $empleado->turno no existe."; return false; }
        if (DB::connection('empresa')->table("Supervisor_giro.Calendar")->where("clave",$empleado->calendarioInhabil)->count() == 0) { $msjError = "El calendario $empleado->calendarioInhabil no existe."; return false; }

        if ($msjError == "") return true;

        return false;
    }

    private function insertaRegistroEmpPrin($empleado)
    {
        $EmpPrin = new EmpPrin;
        $EmpPrin->CLAVE               = trim($empleado->clave);
        $EmpPrin->NOMBREN             = trim($empleado->nombre);
        $EmpPrin->NOMBREP             = trim($empleado->apellidoPaterno);
        $EmpPrin->NOMBREM             = trim($empleado->apellidoMaterno);
        $EmpPrin->RFC                 = trim($empleado->rfc);
        $EmpPrin->CURP                = trim($empleado->curp);
        $EmpPrin->INGRESO             = trim($empleado->fechaIngreso);
        $EmpPrin->ACTIVA_JEFE         = 0;
        $EmpPrin->ACCESO_WEB          = 0;
        $EmpPrin->PE                  = $empleado->tipoEmpleado;
        $EmpPrin->TIPO_PAGO           = $empleado->tipoPago;
        $EmpPrin->AFILIACION          = $empleado->afiliacion;
        $EmpPrin->CALENDARIO          = $empleado->calendarioInhabil;
        $EmpPrin->BANCO               = $empleado->banco;
        $EmpPrin->CLABE_INTERBANCARIA = $empleado->clabe;
        $EmpPrin->CTA_CHEQUES         = $empleado->cuentaCheques;
        $EmpPrin->CTA_TARJETA         = $empleado->cuentaTarjeta;
        // $EmpPrin->TURNOINT            = $empleado->turno;
        $EmpPrin->REGIMEN             = $empleado->regimen;
        $EmpPrin->TIPO_CONTRATO       = $empleado->tipoContrato;
        $EmpPrin->SEMANA_REDUCIDA     = $empleado->semanaReducida;
        $EmpPrin->JORNADA_REDUCIDA    = $empleado->jornadaReducida;
        $EmpPrin->CREDITO_INFONAVIT   = $empleado->numeroCredito;
        $EmpPrin->TIPO_DESCUENTO      = $empleado->tipoDescuento;
        $EmpPrin->VALOR_DESCUENTO     = $empleado->valorDescuento;
        $EmpPrin->INICIO_DESCUENTO_INF= $empleado->inicioDescuento;
        $EmpPrin->FECHA_CAPTURA       = now();

        if ($empleado->derechoPTU             == true) $EmpPrin->PTU = 1;            else $EmpPrin->PTU = 0;
        if ($empleado->descuentoCuotaSindical == true) $EmpPrin->CUOTA_SINDICAL = 1; else $EmpPrin->CUOTA_SINDICAL = 0;

        $EmpPrin->save();
    }

    private function insertaRegistroEmpExt($empleado)
    {
        $EmpExt = new EmpExt;
        $EmpExt->CLAVE                = $empleado->clave;
        $EmpExt->EMAIL                = $empleado->correoLaboral;
        // $EmpExt->AREA                 = $empleado->area_code;
        // $EmpExt->LINEA                = $empleado->linea_code;
        // $EmpExt->FECHA_FOI            = $empleado->age_date_foi;
        // $EmpExt->JOB_CATEGORY         = $empleado->job_category;
        // $EmpExt->CLASIFICACION        = $empleado->classification;
        // $EmpExt->NOMBRE_FISCAL        = $empleado->fiscal_name;
        $EmpExt->CODIGO_POSTAL_FISCAL = $empleado->codigoPostalFiscal;
        $EmpExt->save();
    }

    private function insertaRegistroEmpSdo($empleado, $tipo = "A")
    {
        $EmpSdo = new EmpSdo;
        $EmpSdo->CLAVE         = $empleado->clave;
        $EmpSdo->FECHA         = $empleado->fechaIngreso;
        $EmpSdo->TIPO          = $tipo;
        $EmpSdo->SDO1          = $empleado->salarioDiario;
        $EmpSdo->SDO3          = $empleado->salarioDiarioIntegrado;
        $EmpSdo->SDO5          = $empleado->salarioDiarioIntegrado;
        $EmpSdo->FECHA_CAPTURA = now();
        $EmpSdo->FECHA_SALIDA  = self::FECHASALIDA;
        $EmpSdo->save();
    }

    private function insertaRegistroEmpTipoEmp($empleado)
    {
        $EmpTipoEmp = new EmpTipoEmp;
        $EmpTipoEmp->CLAVE = $empleado->clave;
        $EmpTipoEmp->FECHA_ENTRADA = $empleado->fechaIngreso;
        $EmpTipoEmp->FECHA_CAPTURA = now();
        // $EmpTipoEmp->CATALOGO = $empleado->prestacion;
        $EmpTipoEmp->save();
    }

    private function insertaRegistroEmpRH($empleado)
    {
        $EmpRh = new EmpRh;
        $EmpRh->CLAVE                 = $empleado->clave;
        $EmpRh->CALLE                 = $empleado->calle;
        $EmpRh->NUMERO                = $empleado->numero;
        $EmpRh->INTERIOR              = $empleado->numeroInterior;
        $EmpRh->COLONIA               = $empleado->colonia;
        $EmpRh->CP                    = $empleado->codigoPostal;
        $EmpRh->MUNICIPIO             = $empleado->municipio;
        $EmpRh->ESTADO                = $empleado->estadoDomicilio;
        $EmpRh->TELEFONO_1            = $empleado->telefono;
        $EmpRh->TELEFONO_2            = $empleado->telefono2;
        $EmpRh->EMERGENCIA_CONTACTO   = $empleado->contactoEmergencia;
        $EmpRh->EMERGENCIA_PARENTESCO = $empleado->parentescoEmergencia;
        $EmpRh->EMERGENCIA_TELEFONO   = $empleado->telefonoEmergencia;
        $EmpRh->LUGAR_NACIMIENTO      = $empleado->lugarNacimiento;
        $EmpRh->ESTADO_CIVIL          = $empleado->estadoCivil;
        $EmpRh->SEXO                  = $empleado->sexo;
        $EmpRh->TELEFONO_CELULAR      = $empleado->numCelular;
        $EmpRh->ESTADO_NACIMIENTO     = $empleado->estadoNacimiento;
        $EmpRh->NACIONALIDAD          = $empleado->nacionalidad;
        $EmpRh->EMAIL                 = $empleado->correoPersonal;

        if (trim($empleado->fechaNacimiento) != "") $EmpRh->FECHA_NACIMIENTO = $empleado->fechaNacimiento;

        $EmpRh->save();
    }

    private function insertaRegistroEmpFiscal($empleado)
    {
        $rows  = EmpFiscal::where("CLAVE",$empleado->clave)->count();

        if ($rows > 0)
            $EmpFiscal = EmpFiscal::find($empleado->clave);
        else
            $EmpFiscal = new EmpFiscal;

        $EmpFiscal->CLAVE = $empleado->clave;
        $EmpFiscal->CP    = $empleado->codigoPostalFiscal;
        $EmpFiscal->save();
    }

    private function insertaRegistroEmpJefe($empleado)
    {
        $claveBase = RfcEmp::select('clave')->where('base_activa',1)->first()->clave;

        $EmpJefe = new EmpJefe;
        $EmpJefe->CLAVE         = $empleado->clave;
        $EmpJefe->FECHA_INICIO  = $empleado->fechaIngreso;
        $EmpJefe->FECHA_FIN     = self::FECHASALIDA;
        $EmpJefe->ID_JEFE       = $empleado->jefeInmediato."-".$claveBase;
        $EmpJefe->FECHA_CAPTURA = now();
        $EmpJefe->save();
    }

    private function insertaRegistroEmpEducacion($empleado)
    {
        $EmpEducacion = new EmpEducacion;
        $EmpEducacion->CLAVE = $empleado->clave;
        $EmpEducacion->NIVEL_ESCOLARIDAD = $empleado->escolaridad;
        $EmpEducacion->save();
    }

    private function insertaRegistroKardexLocalizacion($empleado, $codigoCatalogo, $tabla, $calendario = false)
    {
        if (trim($codigoCatalogo) != "")
        {
            if ($calendario)
            {
                DB::connection('empresa')->table($tabla)->where("clave",trim($empleado->clave))
                ->update([
                    'CATALOGO'      => $codigoCatalogo,
                    'FECHA_CAPTURA' => now()
                ]);
            }
            else
            {
                DB::connection('empresa')->table($tabla)->insert([
                    'CLAVE'         => $empleado->clave,
                    'FECHA_ENTRADA' => $empleado->fechaIngreso,
                    'FECHA_SALIDA'  => self::FECHASALIDA,
                    'CATALOGO'      => $codigoCatalogo,
                    'FECHA_CAPTURA' => now()
                ]);
            }
        }
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

    public function datosEmpleado(Request $request)
    {
        try {
            $claveEmp = "";

            $bodyRequest = false;
            if ($request->rfc)      $bodyRequest = true;
            if ($request->empleado) $bodyRequest = true;

            if ($bodyRequest == false)
            {
                return response()->json([
                    'code' => 400,
                    'success' => false,
                    'message' => "No se detectó el parámetro (empleado/rfc) para la búsqueda"
                ], 400);
            }


            if ($request->rfc)      $count = Empleado::where('rfc',trim($request->rfc))->count();
            if ($request->empleado) $count = Empleado::where('clave',trim($request->empleado))->count();

            if ($count == 0)
            {
                $message = "No se encontró el empleado con clave ".$request->empleado;
                if ($request->rfc) $message = "No se encontró el empleado con RFC ".$request->rfc;

                return response()->json([
                    'code' => 404,
                    'success' => false,
                    'message' => $message
                ], 404);
            }
            else
            {
                if ($request->rfc)      $claveEmp = Empleado::where('rfc',trim($request->rfc))->first()->CLAVE;
                if ($request->empleado) $claveEmp = trim($request->empleado);
            }


            $vigencia = Empleado::select('vigencia')->where('clave',$claveEmp)->first()->vigencia;
            if ($vigencia == 'BAJA')
            {
                $message = "El empleado $claveEmp no está vigente";
                if ($request->rfc) $message = "El empleado con RFC $request->rfc no está vigente";

                return response()->json([
                    'code' => 406,
                    'success' => false,
                    'message' => $message
                ], 406);
            }


            $empleado = Empleado::select(
                        DB::raw("rtrim(clave)                         As clave"),
                        DB::raw("rtrim(nombre_completo)               As nombreCompleto"),
                        DB::raw("rtrim(afiliacion)                    As numeroSeguroSocial"),
                        DB::raw("IsNull(rtrim(turno),'')              As turno"),
                        DB::raw("IsNull(rtrim(descripcion_turno),'')  As descripcionTurno"),
                        DB::raw("IsNull(rtrim(puesto),'')             As puesto"),
                        DB::raw("IsNull(rtrim(descripcion_puesto),'') As descripcionPuesto"),
                        DB::raw("Convert(varchar, ingreso, 23) As ingreso"),
                        DB::raw("Convert(varchar, fecha_nacimiento, 23) As fechaNacimiento"),
                        DB::raw("'' As calle"),
                        DB::raw("'' As numeroExterior"),
                        DB::raw("'' As numeroInterior"),
                        DB::raw("'' As colonia"),
                        DB::raw("'' As codigoPostal"),
                        DB::raw("'' As municipio"),
                        DB::raw("'' As estado"),
                        DB::raw("'' As sueldo"),
                        DB::raw("'' As registroPatronal"),
                        DB::raw("'' As razonSocial"),
                        DB::raw("'' As rfcRazonSocial"),
                        DB::raw("'' As calleRazonSocial"),
                        DB::raw("'' As numeroExteriorRazonSocial"),
                        DB::raw("'' As numeroInteriorRazonSocial"),
                        DB::raw("'' As coloniaRazonSocial"),
                        DB::raw("'' As codigoPostalRazonSocial"),
                        DB::raw("'' As municipioRazonSocial"),
                        DB::raw("'' As estadoRazonSocial"),
                    )
                ->where('clave',$claveEmp)
                ->first();

            // sueldo empleado
            $sueldo = EmpSdo::select('sdo1')->where("clave",$claveEmp)->orderby("fecha","desc")->first()->sdo1;
            $empleado->sueldo = round($sueldo,2);

            // domicilio empleado
            $empRh = EmpRh::select(
                    DB::raw("IsNull(rtrim(calle),'')     As calle"),
                    DB::raw("IsNull(rtrim(numero),'')    As numero"),
                    DB::raw("IsNull(rtrim(interior),'')  As interior"),
                    DB::raw("IsNull(rtrim(colonia),'')   As colonia"),
                    DB::raw("IsNull(rtrim(cp),'')        As cp"),
                    DB::raw("IsNull(rtrim(municipio),'') As municipio"),
                    DB::raw("IsNull(rtrim(estado),'')    As estado")
                )
                ->where("clave",$claveEmp)
                ->first();

            $empleado->calle          = $empRh->calle;
            $empleado->numeroExterior = $empRh->numero;
            $empleado->numeroInterior = $empRh->interior;
            $empleado->colonia        = $empRh->colonia;
            $empleado->codigoPostal   = $empRh->cp;
            $empleado->municipio      = $empRh->municipio;
            $empleado->estado         = $empRh->estado;

            // registro patronal
            $patronal = EmpPatronal::select(
                                DB::raw("IsNull(rtrim(Patronal.registro_patronal),'') As registro_patronal"),
                                DB::raw("IsNull(rtrim(Patronal.nombre),'')            As nombre"),
                                DB::raw("IsNull(rtrim(Patronal.rfc),'')               As rfc"),
                                DB::raw("IsNull(rtrim(Patronal.calle),'')             As calle"),
                                DB::raw("IsNull(rtrim(Patronal.numero),'')            As numero"),
                                DB::raw("IsNull(rtrim(Patronal.interior),'')          As interior"),
                                DB::raw("IsNull(rtrim(Patronal.colonia),'')           As colonia"),
                                DB::raw("IsNull(rtrim(Patronal.c_p),'')               As c_p"),
                                DB::raw("IsNull(rtrim(Patronal.municipio),'')         As municipio"),
                                DB::raw("IsNull(rtrim(Patronal.estado),'')            As estado")
                            )
                ->join("Supervisor_giro.Patronal As Patronal","Supervisor_giro.EmpPatronal.catalogo","Patronal.clave")
                ->where("Supervisor_giro.EmpPatronal.clave",$claveEmp)
                ->first();

            $empleado->registroPatronal          = $patronal->registro_patronal;
            $empleado->razonSocial               = $patronal->nombre;
            $empleado->rfcRazonSocial            = $patronal->rfc;
            $empleado->calleRazonSocial          = $patronal->calle;
            $empleado->numeroExteriorRazonSocial = $patronal->numero;
            $empleado->numeroInteriorRazonSocial = $patronal->interior;
            $empleado->coloniaRazonSocial        = $patronal->colonia;
            $empleado->codigoPostalRazonSocial   = $patronal->c_p;
            $empleado->municipioRazonSocial      = $patronal->municipio;
            $empleado->estadoRazonSocial         = $patronal->estado;


            return response()->json([
                'code' => 200,
                'success' => true,
                'empleado' => $empleado,
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'code' => 500,
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

}
