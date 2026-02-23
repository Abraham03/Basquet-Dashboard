<?php
/**
 * Clase BaseController
 * Provee utilidades de validación y sanitización para los controladores.
 */
abstract class BaseController {
    
    /**
     * Valida los datos de entrada según un conjunto de reglas.
     * Ejemplo: ['name' => 'required', 'team_id' => 'required|integer', 'team_side' => 'in:A,B']
     */
    protected function validate(array $data, array $rules): void {
        $errors = [];

        foreach ($rules as $field => $fieldRules) {
            $ruleList = explode('|', $fieldRules);
            $value = $data[$field] ?? null;
            
            // Verificamos si la regla 'required' está en la lista para este campo
            $isRequired = in_array('required', $ruleList);

            foreach ($ruleList as $rule) {
                // 1. Regla: Obligatorio
                if ($rule === 'required') {
                    if ($value === null || (is_string($value) && trim($value) === '')) {
                        $errors[$field] = "El campo '$field' es obligatorio.";
                        break; // Detener otras validaciones si está vacío
                    }
                }

                // Si el valor está vacío y no es requerido, pasamos al siguiente campo
                if (empty($value) && !$isRequired) {
                    continue;
                }

                // 2. Regla: Entero (Ideal para IDs de foreign keys en tu BD)
                if ($rule === 'integer' && filter_var($value, FILTER_VALIDATE_INT) === false) {
                    $errors[$field] = "El campo '$field' debe ser un número entero válido.";
                }

                // 3. Regla: Numérico (Acepta decimales si en el futuro los necesitas)
                if ($rule === 'numeric' && !is_numeric($value)) {
                    $errors[$field] = "El campo '$field' debe ser numérico.";
                }

                // 4. Regla: Enum (Ideal para 'status' o 'team_side')
                // Formato de uso: 'in:A,B' o 'in:ACTIVE,FINISHED'
                if (strpos($rule, 'in:') === 0) {
                    $allowedValues = explode(',', substr($rule, 3));
                    if (!in_array($value, $allowedValues)) {
                        $errors[$field] = "El valor de '$field' no es válido. Opciones permitidas: " . implode(', ', $allowedValues) . ".";
                    }
                }
            }
        }

        // Si se encontraron errores, lanzar la respuesta y detener la ejecución
        if (!empty($errors)) {
            // Unimos los errores en un solo string para el alert de JS, o enviamos el array completo
            $firstErrorMessage = reset($errors);
            Response::error($firstErrorMessage, Response::HTTP_BAD_REQUEST, $errors);
        }
    }

    /**
     * Sanitiza los datos de entrada previniendo XSS.
     */
    protected function sanitize(array $data): array {
        $clean = [];
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $clean[$key] = $this->sanitize($value);
            } else {
                $clean[$key] = is_string($value) ? htmlspecialchars(strip_tags(trim($value)), ENT_QUOTES, 'UTF-8') : $value;
            }
        }
        return $clean;
    }
}
?>