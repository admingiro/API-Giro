<?php

namespace App\Http\Controllers\Plasencia;

use App\Http\Controllers\Plasencia\RecursosController;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Exception;

class ReportesController extends Controller
{
    // conts RH001B, RH001C, RH002
    // const COLABORADORES_ALTAS 
    // const COLABORADORES_BAJAS 
    // const COLABORADORES_ACTIVOS 

    const campoClv                     = "Clave Empleado";
    const campoNom                     = "Nombre(s)";
    const campoAp                      = "Apellido Paterno";
    const campoAm                      = "Apellido Materno";
    const campoRz                      = "Razón Social";
    const campoFechaIngreso            = "Fecha Ingreso";
    const campoCorreoPersonal          = "Correo Personal";
    const campoClvJefe                 = "Clave Jefe";
    const campoNomJefe                 = "Nombre Jefe";
    const campoTipoVacante             = "Tipo de Vacante";
    const campoFechaGeneracionRegistro = "Fecha Generación de Registro";
    const campoNumCelular              = "Número Celular";

    public function __construct()
    {
        $recursos = new RecursosController;
        $this->path = $recursos->baseActiva();
        \Config::set('database.connections.empresa.database',$this->path);
    }

    public function reporteRH001B(Request $request)
    {
        $campoClv                     = self::campoClv;
        $campoNom                     = self::campoNom;
        $campoAp                      = self::campoAp;
        $campoAm                      = self::campoAm;
        $campoRz                      = self::campoRz;
        $campoFechaIngreso            = self::campoFechaIngreso;
        $campoCorreoPersonal          = self::campoCorreoPersonal;
        $campoClvJefe                 = self::campoClvJefe;
        $campoNomJefe                 = self::campoNomJefe;
        $campoTipoVacante             = self::campoTipoVacante;
        $campoFechaGeneracionRegistro = self::campoFechaGeneracionRegistro;
        $campoNumCelular              = self::campoNumCelular;

        try {
            $empleado     = $request->numero_empleado;
            $sucursal     = $request->sucursal;
            $marca        = $request->marca;
            $razonSocial  = $request->razon_social;
            $fechaIni     = $request->fecha_desde;
            $fechaFin     = $request->fecha_hasta;
            $fechaIngreso = $request->fecha_ingreso;
            $fechaGeneracionRegistro = $request->fecha_generacion_registro;

            $script = "SET NOCOUNT ON Exec Supervisor_giro.SP_RH001B_API '$fechaIni','$fechaFin','$empleado','$sucursal','$marca','$razonSocial','$fechaIngreso','$fechaGeneracionRegistro'";
            $reporte = DB::connection('empresa')->select($script);

            $arrayDatos = array();
            foreach ($reporte as $value)
            {
                $registro = [
                    'numero_empleado'           => trim($value->$campoClv),
                    'nombres'                   => trim($value->$campoNom),
                    'apellido_paterno'          => trim($value->$campoAp),
                    'apellido_materno'          => trim($value->$campoAm),
                    'curp'                      => trim($value->CURP),
                    'razon_social'              => trim($value->$campoRz),
                    'departamento'              => trim($value->Departamento),
                    'sucursal'                  => trim($value->Sucursal),
                    'puesto'                    => trim($value->Puesto),
                    'fecha_ingreso'             => trim($value->$campoFechaIngreso),
                    'correo_personal'           => trim($value->$campoCorreoPersonal),
                    'clave_jefe'                => trim($value->$campoClvJefe),
                    'nombre_jefe'               => trim($value->$campoNomJefe),
                    'tipo_vacante'              => trim($value->$campoTipoVacante),
                    'comentarios'               => trim($value->Comentarios),
                    'fecha_generacion_registro' => trim($value->$campoFechaGeneracionRegistro),
                    'movimiento'                => trim($value->Movimiento),
                    'numero_celular'            => trim($value->$campoNumCelular),
                    'ciudad'                    => trim($value->Ciudad),
                    'marca'                     => trim($value->Marca),
                ];

                array_push($arrayDatos, $registro);
            }

            $meta = array();
            array_push($meta, ['reporte' => 'RH001B', 'fecha_consulta' => now(), 'total_registros' => count($arrayDatos)]);
            array_push($meta, ['data'=> $arrayDatos]);

            return ['meta' => $meta];
        } catch (Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }

    public function reporteRH001C(Request $request)
    {
        $campoClv                     = self::campoClv;
        $campoNom                     = self::campoNom;
        $campoAp                      = self::campoAp;
        $campoAm                      = self::campoAm;
        $campoRz                      = self::campoRz;
        $campoFechaIngreso            = self::campoFechaIngreso;
        $campoCorreoPersonal          = self::campoCorreoPersonal;
        $campoClvJefe                 = self::campoClvJefe;
        $campoNomJefe                 = self::campoNomJefe;
        $campoTipoVacante             = self::campoTipoVacante;
        $campoFechaGeneracionRegistro = self::campoFechaGeneracionRegistro;
        $campoNumCelular              = self::campoNumCelular;
        $campoFechaBaja               = "Fecha Baja";
        $campoMotivoBaja              = "Motivo Baja";
        $campoFechaCreacion           = "Fecha Creación";
        $campoFolioEquipoComputo      = "Folio Equipo Computo";
        $campoCelInstitucional        = "Celular Institucional";

        try {
            $empleado    = $request->numero_empleado;
            $sucursal    = $request->sucursal;
            $marca       = $request->marca;
            $razonSocial = $request->razon_social;
            $fechaIni    = $request->fecha_desde;
            $fechaFin    = $request->fecha_hasta;
            $fechaBaja   = $request->fecha_baja;
            $fechaGeneracionRegistro = $request->fecha_generacion_registro;

            $script = "SET NOCOUNT ON Exec Supervisor_giro.SP_RH001C_API '$fechaIni','$fechaFin','$empleado','$sucursal','$marca','$razonSocial','$fechaBaja','$fechaGeneracionRegistro'";
            $reporte = DB::connection('empresa')->select($script);

            $arrayDatos = array();
            foreach ($reporte as $value)
            {
                $registro = [
                    'numero_empleado'           => trim($value->$campoClv),
                    'nombres'                   => trim($value->$campoNom),
                    'apellido_paterno'          => trim($value->$campoAp),
                    'apellido_materno'          => trim($value->$campoAm),
                    'curp'                      => trim($value->CURP),
                    'razon_social'              => trim($value->$campoRz),
                    'departamento'              => trim($value->Departamento),
                    'sucursal'                  => trim($value->Sucursal),
                    'puesto'                    => trim($value->Puesto),
                    'fecha_ingreso'             => trim($value->$campoFechaIngreso),
                    'fecha_baja'                => trim($value->$campoFechaBaja),
                    'motivo_baja'               => trim($value->$campoMotivoBaja),
                    'dominio'                   => trim($value->DOMINIO),
                    'correo_personal'           => trim($value->$campoCorreoPersonal),
                    'fecha_creacion'            => date('Y-m-d', strtotime($value->$campoFechaCreacion)),
                    'folio_equipo_computo'      => trim($value->$campoFolioEquipoComputo),
                    'clave_jefe'                => trim($value->$campoClvJefe),
                    'nombre_jefe'               => trim($value->$campoNomJefe),
                    'celular_institucional'     => trim($value->$campoCelInstitucional),
                    'comentarios'               => trim($value->Comentarios),
                    'fecha_generacion_registro' => date('Y-m-d', strtotime($value->$campoFechaGeneracionRegistro)),
                    'movimiento'                => trim($value->Movimiento),
                    'numero_celular'            => trim($value->$campoNumCelular),
                    'ciudad'                    => trim($value->Ciudad),
                    'marca'                     => trim($value->Marca),
                ];

                array_push($arrayDatos, $registro);
            }

            $meta = array();
            array_push($meta, ['reporte' => 'RH001C', 'fecha_consulta' => now(), 'total_registros' => count($arrayDatos)]);
            array_push($meta, ['data'=> $arrayDatos]);

            return ['meta' => $meta];
        } catch (Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }

    public function reporteRH002(Request $request)
    {
        $campoNom                = self::campoNom;
        $campoAp                 = self::campoAp;
        $campoAm                 = self::campoAm;
        $campoJefeInmediato      = "Jefe Inmediato";
        $campoJefeOrganizacional = "Jefe Organizacional";
        $campoTipoNomina         = "Tipo de Nómina";
        $campoTel1               = "TEL 1";
        $campoTel2               = "TEL 2";
        $campoClvJefe            = "CLV JEFE";

        try {
            $empleado     = $request->numero_empleado;
            $sucursal     = $request->sucursal;
            $marca        = $request->marca;
            $razonSocial  = $request->razon_social;

            $script = "SET NOCOUNT ON Exec Supervisor_giro.SP_RH002_API '$empleado','$sucursal','$marca','$razonSocial'";
            $reporte = DB::connection('empresa')->select($script);

            $arrayDatos = array();
            foreach ($reporte as $value)
            {
                $registro = [
                    'numero_empleado'        => trim($value->CLAVE),
                    'nombre_completo'        => trim($value->NOMBRE),
                    'sucursal'               => trim($value->SUCURSAL),
                    'puesto'                 => trim($value->PUESTO),
                    'departamento'           => trim($value->DEPARTAMENTO),
                    'rfc'                    => trim($value->RFC),
                    'numero_imss'            => trim($value->No_IMSS),
                    'fecha_ingreso'          => trim($value->F_INGRESO),
                    'fecha_alta'             => trim($value->F_ALTA),
                    'curp'                   => trim($value->CURP),
                    'correo_personal'        => trim($value->CORREO_PERSONAL),
                    'registro_patronal'      => trim($value->REGISTRO_PATRONAL),
                    'fecha_antiguedad_grupo' => trim($value->ANTIGUEDAD_GRUPO),
                    'fecha_nacimiento'       => trim($value->FECHA_NACIMIENTO),
                    'edad_empleado'          => trim($value->EDADEMPLEADO),
                    'antiguedad'             => trim($value->ANTIGUEDAD),
                    'genero'                 => trim($value->GENERO),
                    'correo_institucional'   => trim($value->CORREO_INSTITUCIONAL),
                    'jefe_inmediato'         => trim($value->$campoJefeInmediato),
                    'jefe_organizacional'    => trim($value->$campoJefeOrganizacional),
                    'tipo_nomina'            => trim($value->$campoTipoNomina),
                    'apellido_paterno'       => trim($value->$campoAp),
                    'apellido_materno'       => trim($value->$campoAm),
                    'nombres'                => trim($value->$campoNom),
                    'telefono_1'             => trim($value->$campoTel1),
                    'telefono_2'             => trim($value->$campoTel2),
                    'celular'                => trim($value->CELULAR),
                    'clave_jefe'             => trim($value->$campoClvJefe),
                    'nombre_jefe'            => trim($value->JEFE),
                ];

                array_push($arrayDatos, $registro);
            }

            $meta = array();
            array_push($meta, ['reporte' => 'RH002', 'fecha_consulta' => now(), 'total_registros' => count($arrayDatos)]);
            array_push($meta, ['data'=> $arrayDatos]);

            return ['meta' => $meta];
        } catch (Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }

    // private function ejecutaProcedimiento($procedimiento, $empleado, $sucursal, $marca, $razonSocial, $fechaIni, $fechaFin, $tipoFecha)
    // {
    //     // DB::select('exec my_stored_procedure(?,?,..)',array($Param1,$param2));
    //     DB::connection('empresa')->select($procedimiento);
    // }

}
