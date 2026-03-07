<?php
/**
 * ShopWise AI - Input Validator
 *
 * Validates form data against a set of rules.
 * Returns a result array with 'valid' flag and 'errors' per field.
 *
 * Usage:
 *   $result = Validator::validate($_POST, [
 *       'product_name' => ['required', 'min_length:2', 'max_length:100'],
 *       'selling_price' => ['required', 'decimal'],
 *       'email'        => ['required', 'email'],
 *   ]);
 *   if (!$result['valid']) { ... }
 *
 * @package ShopWiseAI\Core
 */

declare(strict_types=1);

class Validator
{
    /**
     * Validate input data against a set of rules.
     *
     * @param array $data   Associative array of field => value
     * @param array $rules  Associative array of field => [rule, rule:param, ...]
     * @return array ['valid'=>bool, 'errors'=>[field => [messages]]]
     */
    public static function validate(array $data, array $rules): array
    {
        $errors = [];

        foreach ($rules as $field => $fieldRules) {
            $value      = $data[$field] ?? null;
            $fieldLabel = ucfirst(str_replace('_', ' ', $field));

            foreach ($fieldRules as $rule) {
                $ruleName  = $rule;
                $ruleParam = null;

                // Parse rule:param format
                if (str_contains($rule, ':')) {
                    [$ruleName, $ruleParam] = explode(':', $rule, 2);
                }

                $error = self::applyRule($ruleName, $ruleParam, $value, $field, $fieldLabel, $data);

                if ($error !== null) {
                    $errors[$field][] = $error;
                    break; // Stop validating this field on first error
                }
            }
        }

        return [
            'valid'  => empty($errors),
            'errors' => $errors,
        ];
    }

    /**
     * Apply a single validation rule.
     *
     * @return string|null  Error message, or null if rule passed
     */
    private static function applyRule(
        string  $rule,
        ?string $param,
        mixed   $value,
        string  $field,
        string  $label,
        array   $allData
    ): ?string {
        switch ($rule) {

            case 'required':
                if ($value === null || trim((string)$value) === '') {
                    return "{$label} is required.";
                }
                break;

            case 'min':
                if ($value !== null && (float)$value < (float)$param) {
                    return "{$label} must be at least {$param}.";
                }
                break;

            case 'max':
                if ($value !== null && (float)$value > (float)$param) {
                    return "{$label} must not exceed {$param}.";
                }
                break;

            case 'min_length':
                if ($value !== null && strlen((string)$value) < (int)$param) {
                    return "{$label} must be at least {$param} characters.";
                }
                break;

            case 'max_length':
                if ($value !== null && strlen((string)$value) > (int)$param) {
                    return "{$label} must not exceed {$param} characters.";
                }
                break;

            case 'email':
                if ($value !== null && !empty($value) && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                    return "{$label} must be a valid email address.";
                }
                break;

            case 'numeric':
                if ($value !== null && !empty($value) && !is_numeric($value)) {
                    return "{$label} must be a number.";
                }
                break;

            case 'decimal':
                if ($value !== null && !empty($value) && !preg_match('/^\d+(\.\d{1,4})?$/', (string)$value)) {
                    return "{$label} must be a valid decimal number.";
                }
                break;

            case 'integer':
                if ($value !== null && !empty($value) && !preg_match('/^\d+$/', (string)$value)) {
                    return "{$label} must be a whole number.";
                }
                break;

            case 'date':
                if ($value !== null && !empty($value)) {
                    $d = \DateTime::createFromFormat('Y-m-d', (string)$value);
                    if (!$d || $d->format('Y-m-d') !== $value) {
                        return "{$label} must be a valid date (YYYY-MM-DD).";
                    }
                }
                break;
            case 'in_list':
                if ($value !== null && !empty($value)) {
                    $allowed = explode(',', $param ?? '');
                    if (!in_array($value, $allowed, true)) {
                        return "{$label} must be one of: " . implode(', ', $allowed) . ".";
                    }
                }
                break;

            case 'unique':
                // param format: table:column[:exceptId:exceptColumn]
                if ($value !== null && !empty($value)) {
                    $parts    = explode('|', $param ?? '');
                    $tcParts  = explode(':', $parts[0]);
                    $table    = $tcParts[0];
                    $column   = $tcParts[1] ?? $field;
                    $exceptId = null;

                    // Optional: unique:table:column|except:123:id
                    if (isset($parts[1]) && str_starts_with($parts[1], 'except:')) {
                        $exceptParts = explode(':', $parts[1]);
                        $exceptId    = $exceptParts[1] ?? null;
                        $exceptCol   = $exceptParts[2] ?? 'id';
                    }

                    $db   = Database::getInstance();
                    $sql  = "SELECT COUNT(*) FROM `{$table}` WHERE `{$column}` = ?";
                    $bind = [$value];

                    if ($exceptId !== null) {
                        $sql  .= " AND `{$exceptCol}` != ?";
                        $bind[] = $exceptId;
                    }

                    $count = (int)$db->prepare($sql)->execute($bind) ? $db->query("SELECT FOUND_ROWS()")->fetchColumn() : 0;
                    // Re-query properly
                    $stmt  = $db->prepare($sql);
                    $stmt->execute($bind);
                    $count = (int)$stmt->fetchColumn();

                    if ($count > 0) {
                        return "{$label} is already taken.";
                    }
                }
                break;

            case 'regex':
                if ($value !== null && !empty($value) && !preg_match($param, (string)$value)) {
                    return "{$label} format is invalid.";
                }
                break;

            case 'confirmed':
                // Field should match field_confirmation sibling
                $confirmKey = $field . '_confirmation';
                if (($allData[$confirmKey] ?? '') !== $value) {
                    return "{$label} confirmation does not match.";
                }
                break;

            case 'phone':
                if ($value !== null && !empty($value)) {
                    $cleaned = preg_replace('/[\s\-\(\)]/', '', (string)$value);
                    if (!preg_match('/^(\+63|0)[0-9]{9,10}$/', $cleaned)) {
                        return "{$label} must be a valid Philippine phone number.";
                    }
                }
                break;

            case 'positive':
                if ($value !== null && !empty($value) && (float)$value <= 0) {
                    return "{$label} must be a positive number.";
                }
                break;

            case 'url':
                if ($value !== null && !empty($value) && !filter_var($value, FILTER_VALIDATE_URL)) {
                    return "{$label} must be a valid URL.";
                }
                break;
        }

        return null;
    }

    /**
     * Get the first error message for a field.
     *
     * @param array  $errors  Errors array from validate()
     * @param string $field   Field name
     * @return string  First error, or empty string
     */
    public static function firstError(array $errors, string $field): string
    {
        return $errors[$field][0] ?? '';
    }

    /**
     * Check if a specific field has an error.
     *
     * @param array  $errors
     * @param string $field
     * @return bool
     */
    public static function hasError(array $errors, string $field): bool
    {
        return !empty($errors[$field]);
    }
}
