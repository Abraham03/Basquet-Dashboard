<?php
/**
 * Clase Response
 * Responsabilidad: Estandarizar la salida JSON y códigos HTTP.
 */
class Response {
    const HTTP_OK = 200;
    const HTTP_CREATED = 201;
    const HTTP_BAD_REQUEST = 400;
    const HTTP_UNAUTHORIZED = 401;
    const HTTP_NOT_FOUND = 404;
    const HTTP_INTERNAL_SERVER_ERROR = 500;

    public static function json(array $data, int $code = self::HTTP_OK): void {
        if (ob_get_length()) ob_clean(); 
        
        header('Content-Type: application/json; charset=UTF-8');
        http_response_code($code);
        
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit();
    }

    public static function success(string $message = 'Operación exitosa', array $data = [], int $code = self::HTTP_OK): void {
        $response = [
            'status'  => 'success',
            'message' => $message,
            'timestamp' => date('Y-m-d H:i:s')
        ];
        if (!empty($data)) {
            $response['data'] = $data;
        }
        self::json($response, $code);
    }

    public static function error(string $message = 'Ha ocurrido un error', int $code = self::HTTP_BAD_REQUEST, array $errors = []): void {
        $response = [
            'status'  => 'error',
            'message' => $message
        ];
        if (!empty($errors)) {
            $response['errors'] = $errors;
        }
        self::json($response, $code);
    }
}
?>