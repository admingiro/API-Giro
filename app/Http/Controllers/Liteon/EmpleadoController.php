<?php

namespace App\Http\Controllers\Liteon;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use XmlReader;
// use App\Models\EmpFiscal;
use App\Models\EmpPrin;
use App\Models\EmpRh;
// use App\Models\EmpSdo;
// use App\Models\EmpExt;
// use App\Models\EmpJefe;
use App\Models\EmpTipoEmp;
// use App\Models\EmpEducacion;
// use App\Models\Factor;
use App\Models\RfcEmp;
// use App\Models\Empleado;
// use App\Models\EmpPatronal;
// use App\Models\Patronal;
use Exception;

class EmpleadoController extends Controller
{
    const FECHASALIDA = "2099-12-31";
    const API = "https://api10preview.sapsf.com:443/odata/v2";
    const USUARIO = "APIOMNI@liteontechT1";
    const CONTRASENA = "API@APIOMNI";

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

    private function escribeEnBitacora($texto, $update = false)
    {
        $bitacora = "bitacora_store.txt";
        if ($update) $bitacora = "bitacora_update.txt";

        $text = now().' - '.$texto;

        Storage::append($bitacora,$text);
    }

    // protected function enviaErroresPorCorreo($proceso, $problema, $msjError)
    // {
    //     $descDias = ['Domingo','Lunes','Martes','Miéroles','Jueves','Viernes','Sábado'];
    //     $descMeses = ['enero','febrero','marzo','abril','mayo','junio','julio','agosto','septiembre','octubre','noviembre','diciembre'];

    //     $data['fecha']    = $descDias[date('w')]." ".date('j')." de ".$descMeses[date('n')-1]." de ".date('Y')." a las ".date('H:i:s');
    //     $data['problema'] = $problema;
    //     $data['proceso']  = $proceso;
    //     $data['msjError'] = $msjError;

    //     $correos = self::CORREOS_AVISO;
    //     // Mail::send('correos.errores', $data, function ($message) use($correos)   {
    //     //     $message->from(env('MAIL_FROM_ADDRESS', 'notificaciones@giroweb.com.mx'));
    //     //     $message->subject('ERROR proceso API Giro-Marathon'); 
    //     //     $to = explode(";",$correos);
    //     //     $message->to($to);
    //     // });
    // }

    public function consultaApiLiteon()
    {
        set_time_limit(-1);
        try {
            $ruta = self::API.'/EmpJob?';

            // $response = Http::withBasicAuth(self::USUARIO, self::CONTRASENA)->get($ruta);
            $http = Http::withBasicAuth(self::USUARIO, self::CONTRASENA)->get($ruta, [
                '$select' => "userNav/status,customString12,location,payGrade,employeeClass,employmentNav/personIdExternal,employmentNav/userId,employmentNav/personNav/dateOfBirth,employmentNav/personNav/personalInfoNav/gender,employmentNav/personNav/personalInfoNav/preferredName",
                '$expand' => "employmentNav,employmentNav/personNav,employmentNav/personNav/personalInfoNav,userNav",
                //'$filter' => "employmentNav/personNav/dateOfBirth eq '2017-4-1' and employmentNav/userId eq '32431130' and userNav/status in 't','f' and employeeClass ge 53681 and employeeClass le 53689 and ( (payGrade ge '01' and payGrade le '05') or (payGrade in '2A','3A','4A','5A') )"
                '$filter' => "company eq '5620' and (lastModifiedDateTime ge datetimeoffset'2026-08-07T00:00:00Z' and lastModifiedDateTime lt datetimeoffset'2026-08-07T00:00:00Z' or employmentNav/personNav/personalInfoNav/lastModifiedDateTime ge datetimeoffset'2026-08-07T00:00:00Z' and employmentNav/personNav/personalInfoNav/lastModifiedDateTime lt datetimeoffset'2026-08-07T00:00:00Z')"
            ]);

            $xmlStr = $http->body();
            $reader = XmlReader::xml($xmlStr);

            $valores = "";
            while ($reader->read()) {
                if ($reader->nodeType == XMLReader::ELEMENT && $reader->name === 'm:properties') {
                    // Read the contents of the <Product> node
                    $properties = $reader->readOuterXml();
                    // var_dump($properties);

                    // Create a new XMLReader instance to parse the inner <Product> node
                    $innerReader = new XMLReader();
                    $innerReader->xml($properties);

                    // Read inner <Product> node content
                    
                    while ($innerReader->read()) {
                        if ($innerReader->nodeType == XMLReader::ELEMENT) {
                            $innerNodeName = $innerReader->name;
                            $innerNodeValue = $innerReader->readString();

                            switch ($innerNodeName) {
                                case 'd:status':           $valores .= "status:$innerNodeValue>";           break;
                                case 'd:userId':           $valores .= "userId:$innerNodeValue>";           break;
                                case 'd:gender':           $valores .= "gender:$innerNodeValue>";           break;
                                case 'd:preferredName':    $valores .= "preferredName:$innerNodeValue>";    break;
                                case 'd:dateOfBirth':      $valores .= "dateOfBirth:$innerNodeValue>";      break;
                                case 'd:personIdExternal': $valores .= "personIdExternal:$innerNodeValue>"; break;
                                case 'd:employeeClass':    $valores .= "employeeClass:$innerNodeValue>";    break;
                                case 'd:payGrade':         $valores .= "payGrade:$innerNodeValue>";         break;
                                case 'd:location':         $valores .= "location:$innerNodeValue>";         break;
                                case 'd:customString12':   $valores .= "customString12:$innerNodeValue|";   break;
                            }
                        }
                    }
                    // Close the inner XMLReader
                    $innerReader->close();
                }
            }
            
            // Close the main XMLReader
            $reader->close();


            $arrayProperties = array();
            $valores = substr($valores, 0, -1);
            $exp = explode('|',$valores);

            $arrayProperties = array();
            $firstRow = true;
            for ($i=0; $i < count($exp); $i++)
            {
                $exp2 = explode('>',$exp[$i]);

                for ($l=0; $l < count($exp2); $l++)
                {
                    $exp3 = explode(':',$exp2[$l]);

                    $campo = $exp3[0];
                    $valor = "";
                    if (count($exp3) > 0) $valor = $exp3[1];

                    $arrayProperties[$i][$campo] = $valor;
                }

            }

            foreach (json_decode( json_encode($arrayProperties) ) as $key => $value)
            {
                $clave = $value->userId;
                $status = $value->status;
                $genero = $value->gender;
                $nombre = $value->preferredName;

                if ($value->dateOfBirth == "") echo "VACÍO <br>";
                $nacimiento = explode('T',$value->dateOfBirth)[0];
                // $personIdExternal = $value->personIdExternal;
                $employeeClass = $value->employeeClass;
                $payGrade = $value->payGrade;
                $location = $value->location;
                $customString12 = $value->customString12;

                $this->insertaRegistroEmpPrin($clave, $nombre);
                $this->insertaRegistroEmpTipoEmp($clave);
                $this->insertaRegistroEmpRH($clave, $nacimiento, $genero);
            }

            return ['success' => 'Correcto'];
        } catch (\Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }

    private function insertaRegistroEmpPrin($clave, $nombre)
    {
        $rows = EmpPrin::where('clave',$clave)->count();

        if ($rows == 0)
        {
            $EmpPrin = new EmpPrin;
            $EmpPrin->CLAVE = $clave;
            $EmpPrin->FECHA_CAPTURA = now();
            $EmpPrin->INGRESO = now();
        }
        else
            $EmpPrin = EmpPrin::where('clave',$clave)->first();
        
        $EmpPrin->NOMBREN             = $nombre;
        // $EmpPrin->NOMBREP             = trim($empleado->apellidoPaterno);
        // $EmpPrin->NOMBREM             = trim($empleado->apellidoMaterno);
        // $EmpPrin->RFC                 = trim($empleado->rfc);
        // $EmpPrin->CURP                = trim($empleado->curp);
        
        $EmpPrin->ACTIVA_JEFE         = 0;
        $EmpPrin->ACCESO_WEB          = 0;
        // $EmpPrin->PE                  = $empleado->tipoEmpleado;
        // $EmpPrin->TIPO_PAGO           = $empleado->tipoPago;
        // $EmpPrin->AFILIACION          = $empleado->afiliacion;
        // $EmpPrin->CALENDARIO          = $empleado->calendarioInhabil;
        // $EmpPrin->BANCO               = $empleado->banco;
        // $EmpPrin->CLABE_INTERBANCARIA = $empleado->clabe;
        // $EmpPrin->CTA_CHEQUES         = $empleado->cuentaCheques;
        // $EmpPrin->CTA_TARJETA         = $empleado->cuentaTarjeta;
        // $EmpPrin->TURNOINT            = $empleado->turno;
        // $EmpPrin->REGIMEN             = $empleado->regimen;
        // $EmpPrin->TIPO_CONTRATO       = $empleado->tipoContrato;
        // $EmpPrin->SEMANA_REDUCIDA     = $empleado->semanaReducida;
        // $EmpPrin->JORNADA_REDUCIDA    = $empleado->jornadaReducida;
        // $EmpPrin->CREDITO_INFONAVIT   = $empleado->numeroCredito;
        // $EmpPrin->TIPO_DESCUENTO      = $empleado->tipoDescuento;
        // $EmpPrin->VALOR_DESCUENTO     = $empleado->valorDescuento;
        // $EmpPrin->INICIO_DESCUENTO_INF= $empleado->inicioDescuento;
        

        // if ($empleado->derechoPTU             == true) $EmpPrin->PTU = 1;            else $EmpPrin->PTU = 0;
        // if ($empleado->descuentoCuotaSindical == true) $EmpPrin->CUOTA_SINDICAL = 1; else $EmpPrin->CUOTA_SINDICAL = 0;

        $EmpPrin->save();
    }

    private function insertaRegistroEmpTipoEmp($clave)
    {
        $rows = EmpTipoEmp::where('clave',$clave)->count();

        if ($rows == 0)
        {
            $EmpTipoEmp = new EmpTipoEmp;
            $EmpTipoEmp->CLAVE = $clave;
            $EmpTipoEmp->FECHA_CAPTURA = now();
        }
        else
            $EmpTipoEmp = EmpTipoEmp::where('clave',$clave)->first();

        $EmpTipoEmp->FECHA_ENTRADA = date('Y-m-d');
        // $EmpTipoEmp->CATALOGO = $empleado->prestacion;
        $EmpTipoEmp->save();
    }

    private function insertaRegistroEmpRH($clave, $fechaNacimiento, $genero)
    {
        $rows = EmpRh::where('clave',$clave)->count();

        if ($rows == 0)
        {
            $EmpRh = new EmpRh;
            $EmpRh->CLAVE = $clave;
        }
        else
            $EmpRh = EmpRh::where('clave',$clave)->first();

        $EmpRh->FECHA_NACIMIENTO = $fechaNacimiento;
        $EmpRh->SEXO = $genero;

        $EmpRh->save();
    }

}
