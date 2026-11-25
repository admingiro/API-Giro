<?php

namespace App\Http\Controllers\Plasencia;

use App\Http\Controllers\Plasencia\RecursosController;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use App\Models\EmpPrin;
use Exception;

class DcaController extends Controller
{
    // const FECHASALIDA = "2099-12-31";
    const TABLAPERCEPDEDUC = "Supervisor_giro.EmpPercepcionesDca";

    public function __construct()
    {
        $recursos = new RecursosController;
        $this->path = $recursos->baseActiva();
        \Config::set('database.connections.empresa.database',$this->path);
    }

    private function existeEmpleado($clave)
    {
        $count = EmpPrin::where('clave',$clave)->count();

        if ($count > 0) return true;

        return false;
    }

    private function daTipoNomina($clave)
    {
        $tipo_nom = EmpPrin::select('tipo_nom')->where('clave',$clave)->first()->tipo_nom;

        return $tipo_nom;
    }

    private function daPeriodoActual($tipoNom)
    {
        $actual = DB::connection('empresa')->table('Supervisor_giro.Tiponom')->select('actual')->where("clave",$tipoNom)->first()->actual;

        return trim($actual);
    }

    private function percepDeducExistente($clave, $tipoNom, $periodo, &$mensaje)
    {
        $existe = false;
        $tabla  = self::TABLAPERCEPDEDUC;

        $percepDeduc = DB::connection('empresa')->table($tabla)->select("pagado")->where("clave",$clave)->where("tipo_nomina",$tipoNom)->where("periodo",$periodo);

        if ($percepDeduc->count() > 0)
        {
            $existe = true;
            $pagado = $percepDeduc->first()->pagado;

            $mensaje = "El registro para la nómina: ".$tipoNom." y periodo: ".$periodo;

            if ($pagado == 1)
                $mensaje .= " ya fue pagado";
            else
                $mensaje .= " existe, pero esta pendiente por pagar.";
        }

        return $existe;
    }

    private function insertaPercepDeduc($data, $clave, $tipoNom, $periodo)
    {
        $tablaInsert = self::TABLAPERCEPDEDUC;

        $percepciones = json_decode(json_encode($data->percepciones),true);
        $deducciones  = json_decode(json_encode($data->deducciones) ,true);

        DB::connection('empresa')->table($tablaInsert)->insert([
            'clave'         => $clave,
            'tipo_nomina'   => $tipoNom,
            'periodo'       => $periodo,
            'fecha_captura' => now(),

            // Percepciones
            'P03'  => $percepciones["P03"],
            'P08'  => $percepciones["P08"],
            'P27'  => $percepciones["P27"],
            'P40'  => $percepciones["P40"],
            'P41'  => $percepciones["P41"],
            'P41A' => $percepciones["P41A"],
            'P41B' => $percepciones["P41B"],
            'P41C' => $percepciones["P41C"],
            'P41D' => $percepciones["P41D"],
            'P41F' => $percepciones["P41F"],
            'P41G' => $percepciones["P41G"],
            'P41H' => $percepciones["P41H"],
            'P41J' => $percepciones["P41J"],
            'P41K' => $percepciones["P41K"],
            'P41L' => $percepciones["P41L"],
            'P41M' => $percepciones["P41M"],
            'P41N' => $percepciones["P41N"],
            'P41O' => $percepciones["P41O"],
            'P41P' => $percepciones["P41P"],
            'P41R' => $percepciones["P41R"],
            'P41T' => $percepciones["P41T"],
            'P41V' => $percepciones["P41V"],
            'P41W' => $percepciones["P41W"],
            'P41X' => $percepciones["P41X"],
            'P45'  => $percepciones["P45"],
            'P49B' => $percepciones["P49B"],
            'P50G' => $percepciones["P50G"],
            'P50J' => $percepciones["P50J"],

            // Deducciones
            'D55' => $deducciones["D55"],
            'D66' => $deducciones["D66"],
            'D69' => $deducciones["D69"],
            'D70' => $deducciones["D70"],
            'D71' => $deducciones["D71"],
            'D73' => $deducciones["D73"],
            'D74' => $deducciones["D74"],
            'D78' => $deducciones["D78"],
            'D80' => $deducciones["D80"],
            'D81' => $deducciones["D81"],
            'D82' => $deducciones["D82"],
            'D83' => $deducciones["D83"],
            'D84' => $deducciones["D84"],
            'D85' => $deducciones["D85"],
            'D86' => $deducciones["D86"],
            'D87' => $deducciones["D87"],
            'D88' => $deducciones["D88"],
            'D89' => $deducciones["D89"],
            'D90' => $deducciones["D90"],
            'D91' => $deducciones["D91"],
        ]);
    }

    public function percepcionesYdeducciones(Request $request)
    {
        DB::connection('empresa')->beginTransaction();

        try {
            $clave    = "";
            $bitacora = array();

            foreach ($request->datos as $value)
            {
                $data = json_decode(json_encode($value));

                $clave = $data->empleado;
                $existe = $this->existeEmpleado($clave);

                if ($existe)
                {
                    $tipoNom = $this->daTipoNomina($clave);
                    $periodo = $data->periodo;

                    $periodoActual = $this->daPeriodoActual($tipoNom);

                    if ($periodo != $periodoActual)
                    {
                        $error = "El periodo no coincide con el periodo actual";

                        if ($periodo < $periodoActual) $error = "El periodo ".$periodo.", ya esta cerrado.";
                        if ($periodo > $periodoActual) $error = "El periodo ".$periodo.", aún no se inicia.";

                        $respuestaIndividual = ["empleado" => $clave, "procesado" => false, "error" => $error];
                    }
                    else
                    {
                        $mensaje="";
                        $existe = $this->percepDeducExistente($clave,$tipoNom,$periodo,$mensaje);

                        if ($existe)
                        {
                            $respuestaIndividual = ["empleado" => $clave, "procesado" => false, "error" => $mensaje];
                        }
                        else
                        {
                            $this->insertaPercepDeduc($data,$clave,$tipoNom,$periodo);

                            $respuestaIndividual = ["empleado" => $clave, "procesado" => true, "mensaje" => "Se guardó con éxito el registro"];
                        }
                    }
                }
                else
                {
                    $respuestaIndividual = ["procesado" => false, "error" => "No existe la clave: ".$clave];
                }

                array_push($bitacora,$respuestaIndividual);
            }

            DB::connection('empresa')->commit();

            return response()->json([
                'status' => true,
                'codigo' => 200,
                'bitacora' => $bitacora
            ], 200);

        } catch (Exception $e) {
            DB::connection('empresa')->rollBack();

            $respuesta = "";
            if ($clave != "") $respuesta = 'Error al procesar el empleado: '.$clave.'. ';

            return response()->json([
                'status' => false,
                'codigo' => 500,
                'error' => $respuesta.$e->getMessage()
            ], 500);
        }
    }

}