<?php

namespace App\Http\Controllers\Plasencia;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use App\Models\RfcEmp;
use Exception;

class RecursosController extends Controller
{
    public function __construct()
    {
        $this->path = $this->baseActiva();
        \Config::set('database.connections.empresa.database',$this->path);
    }

    public function baseActiva()
    {
        $path = RfcEmp::select('path_trabajo')->where('base_api_test',1)->first()->path_trabajo;

        return trim($path);
    }

    public function getRegimen()
    {
        return $this->getCatalogo("Regimen");
    }

    public function getJornada()
    {
        return $this->getCatalogo("TipoJornadaCFDI");
    }

    public function getTipoContrato()
    {
        return $this->getCatalogo("TipoContratoCFDI");
    }

    public function getTipoEmpleado()
    {
        return $this->getCatalogo("Tipoemp");
    }

    public function getCalendarioInhabil()
    {
        return $this->getCatalogo("Calendar");
    }

    public function getPatronal()
    {
        return $this->getCatalogo("Patronal");
    }

    public function getDepartamento()
    {
        return $this->getCatalogo("DEPTO");
    }

    public function getPuesto()
    {
        return $this->getCatalogo("Puesto");
    }

    public function getSucursal()
    {
        return $this->getCatalogo("Sucursal");
    }

    public function getTurno()
    {
        return $this->getCatalogo("Turno1");
    }

    public function getTipoNomina()
    {
        return $this->getCatalogo("Tiponom");
    }

    public function getBanco()
    {
        return $this->getCatalogo("Bancos_CFDI");
    }

    public function getTipoPago()
    {
        return $this->getCatalogo("TipoPago");
    }

    public function getEscolaridad()
    {
        return $this->getCatalogo("_NIVEL_EDUCATIVO");
    }

    public function getEstadoCivil()
    {
        return $this->getCatalogo("EDO_CIVIL");
    }

    public function getEstado()
    {
        return $this->getCatalogo("EstadoCFDI");
    }

    public function getParentesco()
    {
        return $this->getCatalogo("Parentesco");
    }

    public function getGenero()
    {
        $data = array();

        array_push($data,["clave" => "M", "descripcion" => "MASCULINO"]);
        array_push($data,["clave" => "F", "descripcion" => "FEMENINO"]);

        return $data;
    }

    // public function getTipoDescuento()
    // {
    //     return $this->getCatalogo("");
    // }

    public function getNacionalidad()
    {
        $data = array();

        array_push($data,["clave" => "AFGANA"        , "descripcion" => "AFGANA"]);
        array_push($data,["clave" => "ÁRABE"         , "descripcion" => "ÁRABE"]);
        array_push($data,["clave" => "ARGENTINA"     , "descripcion" => "ARGENTINA"]);
        array_push($data,["clave" => "AUSTRALIANA"   , "descripcion" => "AUSTRALIANA"]);
        array_push($data,["clave" => "BELICEÑA"      , "descripcion" => "BELICEÑA"]);
        array_push($data,["clave" => "BOLIVIANA"     , "descripcion" => "BOLIVIANA"]);
        array_push($data,["clave" => "BRASILEÑA"     , "descripcion" => "BRASILEÑA"]);
        array_push($data,["clave" => "CAMBOYANA"     , "descripcion" => "CAMBOYANA"]);
        array_push($data,["clave" => "CANADIENSE"    , "descripcion" => "CANADIENSE"]);
        array_push($data,["clave" => "CHILENA"       , "descripcion" => "CHILENA"]);
        array_push($data,["clave" => "COLOMBIANA"    , "descripcion" => "COLOMBIANA"]);
        array_push($data,["clave" => "COSTARRICENSE" , "descripcion" => "COSTARRICENSE"]);
        array_push($data,["clave" => "CUBANA"        , "descripcion" => "CUBANA"]);
        array_push($data,["clave" => "DANESA"        , "descripcion" => "DANESA"]);
        array_push($data,["clave" => "DOMINICANO"    , "descripcion" => "DOMINICANO"]);
        array_push($data,["clave" => "ECUATORIANA"   , "descripcion" => "ECUATORIANA"]);
        array_push($data,["clave" => "EGIPCIA"       , "descripcion" => "EGIPCIA"]);
        array_push($data,["clave" => "ESCOCESA"      , "descripcion" => "ESCOCESA"]);
        array_push($data,["clave" => "ESPAÑOLA"      , "descripcion" => "ESPAÑOLA"]);
        array_push($data,["clave" => "ESTONIA"       , "descripcion" => "ESTONIA"]);
        array_push($data,["clave" => "ESTADOUNIDENSE", "descripcion" => "ESTADOUNIDENSE"]);
        array_push($data,["clave" => "FILIPINA"      , "descripcion" => "FILIPINA"]);
        array_push($data,["clave" => "FRANCESA"      , "descripcion" => "FRANCESA"]);
        array_push($data,["clave" => "GRIEGA"        , "descripcion" => "GRIEGA"]);
        array_push($data,["clave" => "GUATEMALTECA"  , "descripcion" => "GUATEMALTECA"]);
        array_push($data,["clave" => "HAITIANA"      , "descripcion" => "HAITIANA"]);
        array_push($data,["clave" => "HONDUREÑA"     , "descripcion" => "HONDUREÑA"]);
        array_push($data,["clave" => "INGLESA"       , "descripcion" => "INGLESA"]);
        array_push($data,["clave" => "IRANÍ"         , "descripcion" => "IRANÍ"]);
        array_push($data,["clave" => "ISRAELÍ"       , "descripcion" => "ISRAELÍ"]);
        array_push($data,["clave" => "JAPONESA"      , "descripcion" => "JAPONESA"]);
        array_push($data,["clave" => "JAMAICANA"     , "descripcion" => "JAMAICANA"]);
        array_push($data,["clave" => "LAOSIANA"      , "descripcion" => "LAOSIANA"]);
        array_push($data,["clave" => "LETONESA"      , "descripcion" => "LETONESA"]);
        array_push($data,["clave" => "MARROQUÍ"      , "descripcion" => "MARROQUÍ"]);
        array_push($data,["clave" => "NICARAGÜENSE"  , "descripcion" => "NICARAGÜENSE"]);
        array_push($data,["clave" => "NEOZELANDESA"  , "descripcion" => "NEOZELANDESA"]);
        array_push($data,["clave" => "PANAMEÑA"      , "descripcion" => "PANAMEÑA"]);
        array_push($data,["clave" => "PARAGUAYA"     , "descripcion" => "PARAGUAYA"]);
        array_push($data,["clave" => "POLACA"        , "descripcion" => "POLACA"]);
        array_push($data,["clave" => "PUERTORRIQUEÑO", "descripcion" => "PUERTORRIQUEÑO"]);
        array_push($data,["clave" => "RUMANA"        , "descripcion" => "RUMANA"]);
        array_push($data,["clave" => "SALVADOREÑA"   , "descripcion" => "SALVADOREÑA"]);
        array_push($data,["clave" => "SUECA"         , "descripcion" => "SUECA"]);
        array_push($data,["clave" => "SURINAMÉS"     , "descripcion" => "SURINAMÉS"]);
        array_push($data,["clave" => "TAILANDESA"    , "descripcion" => "TAILANDESA"]);
        array_push($data,["clave" => "TURCA"         , "descripcion" => "TURCA"]);
        array_push($data,["clave" => "URUGUAYA"      , "descripcion" => "URUGUAYA"]);
        array_push($data,["clave" => "VENEZOLANA"    , "descripcion" => "VENEZOLANA"]);
        array_push($data,["clave" => "VIETNAMITA"    , "descripcion" => "VIETNAMITA"]);

        return $data;
    }

    private function getCatalogo($tabla)
    {
        try {
            $query = DB::connection('empresa')->table("Supervisor_giro.".$tabla);

            switch ($tabla) {
                case 'Patronal':
                    $query = $query->select(
                        DB::raw("rtrim(clave) As clave"),
                        DB::raw("rtrim(nombre) As descripcion")
                    );
                    break;
                case 'Sucursal':
                    $query = $query->select(
                        DB::raw("rtrim(clave) As clave"),
                        DB::raw("rtrim(nombre) As descripcion")
                    );
                    break;

                case 'Bancos_CFDI':
                    $query = $query->select(
                        DB::raw("rtrim(clave) As clave"),
                        DB::raw("rtrim(nombre_corto) As descripcion")
                    );
                    break;

                case 'EstadoCFDI';
                    $query = $query->select(
                        DB::raw("rtrim(estado) As clave"),
                        DB::raw("rtrim(nombre) As descripcion")
                    );
                    break;

                case 'Parentesco';
                    $query = $query->select(
                        DB::raw("rtrim(clave) As clave"),
                        DB::raw("rtrim(clave) As descripcion")
                    );
                    break;

                case 'Tipoemp':
                    $query = $query->select(
                        DB::raw("rtrim(clave) As clave"),
                        DB::raw("rtrim(descripcion) As descripcion")
                    )->where('clave','<>',99);
                    break;

                default:
                    $query = $query->select(
                        DB::raw("rtrim(clave) As clave"),
                        DB::raw("rtrim(descripcion) As descripcion")
                    );
                    break;
            }

            $data = $query->get();

            return $data;
        } catch (Exception $e) {
            return response()->json([
                'code' => 500,
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
