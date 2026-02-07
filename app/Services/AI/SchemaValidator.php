<?php

namespace App\Services\AI;

class SchemaValidator
{
    /**
     * Validate JSON against expected schema
     */
    public function validate(string $json, ?array $schema = null): array
    {
        $data = json_decode($json, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            return [
                'valid' => false,
                'error' => 'Invalid JSON: ' . json_last_error_msg(),
            ];
        }

        if ($schema) {
            $errors = $this->validateAgainstSchema($data, $schema);
            if (!empty($errors)) {
                return [
                    'valid' => false,
                    'errors' => $errors,
                ];
            }
        }

        return ['valid' => true, 'data' => $data];
    }

    /**
     * Validate data against schema
     */
    protected function validateAgainstSchema(array $data, array $schema): array
    {
        $errors = [];

        foreach ($schema as $key => $rules) {
            if (isset($rules['required']) && $rules['required'] && !isset($data[$key])) {
                $errors[] = "Missing required field: {$key}";
                continue;
            }

            if (!isset($data[$key])) {
                continue;
            }

            if (isset($rules['type'])) {
                $type = gettype($data[$key]);
                $expectedType = $rules['type'];
                
                if ($type !== $expectedType && !($expectedType === 'array' && is_array($data[$key]))) {
                    $errors[] = "Field {$key} must be {$expectedType}, got {$type}";
                }
            }

            if (isset($rules['properties']) && is_array($data[$key])) {
                $nestedErrors = $this->validateAgainstSchema($data[$key], $rules['properties']);
                $errors = array_merge($errors, $nestedErrors);
            }
        }

        return $errors;
    }
}
