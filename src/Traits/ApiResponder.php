<?php

namespace Motor\Admin\Traits;

/*
|--------------------------------------------------------------------------
| Api Responder Trait
|--------------------------------------------------------------------------
|
| This trait will be used for any response we sent to clients.
|
*/

trait ApiResponder
{
    /**
     * Return a success JSON response.
     *
     * @param array $data
     * @param string|null $message
     * @param int $code
     * @return \Illuminate\Http\JsonResponse
     */
    protected function success(array $data, string $message = null, int $code = 200)
    {
        return response()->json([
            'status'  => 'Success',
            'message' => $message,
            'data'    => $data,
        ], $code);
    }

    /**
     * Return an error JSON response.
     *
     * @param string|null $message
     * @param int $code
     * @param array|null $data
     * @return \Illuminate\Http\JsonResponse
     */
    protected function error(string $message = null, int $code = 500, array $data = null)
    {
        return response()->json([
            'status'  => 'Error',
            'message' => $message,
            'data'    => $data,
        ], $code);
    }
}
