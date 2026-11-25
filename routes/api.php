<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

// Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
//     return $request->user();
// });

Route::post("token",[AuthController::class,'login']);

Route::group(['middleware' => 'auth:sanctum'], function () {
    Route::get('logout', [AuthController::class,'logout']);

    // Route::namespace("App\\Http\\Controllers\\Marathon")->group(function () {
    //     Route::post("/employees/store", "EmpleadoController@guarda");
    //     Route::put("/employees/update", "EmpleadoController@actualiza");
    //     Route::put("/employees/delete", "EmpleadoController@bajaEmpleado");
    //     Route::get("/employees/typesofdischarge", "EmpleadoController@getTiposDeBaja");
    // });

    Route::namespace("App\\Http\\Controllers\\Plasencia")->group(function () {
        // Route::get("/empleado/ObtieneEmpleados", "EmpleadoController@vigentes");
        Route::post("/empleado", "EmpleadoController@nuevo");
        Route::get("/empleado/getEmpleado", "EmpleadoController@datosEmpleado");
        Route::get("/Regimen", "RecursosController@getRegimen");
        Route::get("/Jornada", "RecursosController@getJornada");
        Route::get("/TipoContrato", "RecursosController@getTipoContrato");
        Route::get("/TipoEmpleado", "RecursosController@getTipoEmpleado");
        Route::get("/CalendarioInhabil", "RecursosController@getCalendarioInhabil");
        Route::get("/Patronal", "RecursosController@getPatronal");
        Route::get("/Departamento", "RecursosController@getDepartamento");
        Route::get("/Puesto", "RecursosController@getPuesto");
        Route::get("/Sucursal", "RecursosController@getSucursal");
        Route::get("/Turno", "RecursosController@getTurno");
        Route::get("/TipoNomina", "RecursosController@getTipoNomina");
        Route::get("/Banco", "RecursosController@getBanco");
        Route::get("/TipoPago", "RecursosController@getTipoPago");
        Route::get("/Nacionalidad", "RecursosController@getNacionalidad");
        Route::get("/Escolaridad", "RecursosController@getEscolaridad");
        Route::get("/Genero", "RecursosController@getGenero");
        Route::get("/EstadoCivil", "RecursosController@getEstadoCivil");
        Route::get("/Estado", "RecursosController@getEstado");
        Route::get("/Parentesco", "RecursosController@getParentesco");
    });
});

Route::get("/config", "App\\Http\\Controllers\\Marathon\\EmpleadoController@index");
Route::put("/employees/update", "App\\Http\\Controllers\\Marathon\\EmpleadoController@actualiza");


Route::get("/empleado/ObtieneEmpleados", "App\\Http\\Controllers\\Plasencia\\EmpleadoController@vigentes");
Route::post("/empleado", "App\\Http\\Controllers\\Plasencia\\EmpleadoController@nuevo");
Route::get("/empleado/getEmpleado", "App\\Http\\Controllers\\Plasencia\\EmpleadoController@datosEmpleado");


Route::post("/dca/percepcionesYdeducciones", "App\\Http\\Controllers\\Plasencia\\DcaController@percepcionesYdeducciones");

Route::get("/v1/colaboradores/altas", "App\\Http\\Controllers\\Plasencia\\ReportesController@reporteRH001B");
Route::get("/v1/colaboradores/bajas", "App\\Http\\Controllers\\Plasencia\\ReportesController@reporteRH001C");
Route::get("/v1/colaboradores/activos", "App\\Http\\Controllers\\Plasencia\\ReportesController@reporteRH002");



Route::get("unauthenticated", function () {
    return response()->json([
        "status" => 401,
        "message" => "Unauthenticated.",
    ], 401);
});

// Route::namespace("App\\Http\\Controllers\\Api")->group(function () {
// 	Route::get("/empleados", "EmpleadoController@index");

//     Route::get("/pass", function () {
//         return \Illuminate\Support\Facades\Hash::make('Pl4s3nc142026!');
//     });

// });
