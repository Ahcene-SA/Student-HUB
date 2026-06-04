<?php
/**
 * Helper to detect duplicate UNIQUE fields from MySQL error 1062 (ER_DUP_ENTRY).
 * Works with both mysqli and PDO.
 *
 * MySQL error message formats:
 *   mysqli: "Duplicate entry 'value' for key 'column_name'"
 *   PDO:    "SQLSTATE[23000]: ... 1062 Duplicate entry 'value' for key 'column_name'"
 *
 * @param string $errorMessage The raw MySQL error message
 * @param array  $fieldMap     Optional mapping of DB column/index names => user-friendly field names
 * @return array{field: string, value: string, message: string}|null
 */
function parseDuplicateEntryError(string $errorMessage, array $fieldMap = []): ?array
{
    // Only handle MySQL error 1062 (ER_DUP_ENTRY)
    if (strpos($errorMessage, '1062') === false && strpos($errorMessage, 'Duplicate entry') === false) {
        return null;
    }

    // Extract the value and key name from the error message
    // Pattern: Duplicate entry '...' for key '...'
    if (!preg_match("/Duplicate entry '(.*?)' for key '(.*?)'/", $errorMessage, $matches)) {
        return null;
    }

    $duplicateValue = $matches[1];
    $keyName        = $matches[2];

    // Determine which field is duplicated
    // The key name might be a column name directly, or a named index like "users_email_unique"
    $field = $keyName;

    // Common cleanup: if key looks like "table.column", take just the column
    if (strpos($field, '.') !== false) {
        $parts = explode('.', $field);
        $field = end($parts);
    }

    // Map to user-friendly name if provided, otherwise clean up common suffixes
    if (isset($fieldMap[$field])) {
        $friendlyField = $fieldMap[$field];
    } else {
        $friendlyField = $field;
        // Strip common index suffixes like _unique, _idx
        $friendlyField = preg_replace('/_unique$/i', '', $friendlyField);
        $friendlyField = preg_replace('/_idx$/i', '', $friendlyField);
    }

    // Build a user-friendly message
    $messages = [
        'username' => "This username is already taken, please choose another one.",
        'email'    => "This email address is already registered.",
    ];

    $friendlyFieldLower = strtolower($friendlyField);
    if (isset($messages[$friendlyFieldLower])) {
        $userMessage = $messages[$friendlyFieldLower];
    } else {
        $userMessage = "The value '$duplicateValue' for '$friendlyField' already exists. Please use a different one.";
    }

    return [
        'field'   => $friendlyField,
        'value'   => $duplicateValue,
        'message' => $userMessage,
    ];
}

/**
 * Get a user-friendly duplicate entry message from a PDOException.
 *
 * @param PDOException $e
 * @param array        $fieldMap Optional column/index name mapping
 * @return array{field: string, value: string, message: string}|null
 */
function getDuplicateErrorFromPDO(PDOException $e, array $fieldMap = []): ?array
{
    // PDOException getCode() returns SQLSTATE '23000', but errorInfo[1] has the MySQL error code
    $errorInfo = $e->errorInfo;
    if (!empty($errorInfo) && isset($errorInfo[1]) && $errorInfo[1] == 1062) {
        return parseDuplicateEntryError($e->getMessage(), $fieldMap);
    }
    return null;
}

/**
 * Get a user-friendly duplicate entry message from a mysqli connection/statement.
 *
 * @param mysqli $conn
 * @param array  $fieldMap Optional column/index name mapping
 * @return array{field: string, value: string, message: string}|null
 */
function getDuplicateErrorFromMysqli(mysqli $conn, array $fieldMap = []): ?array
{
    if ($conn->errno === 1062) {
        return parseDuplicateEntryError($conn->error, $fieldMap);
    }
    return null;
}
