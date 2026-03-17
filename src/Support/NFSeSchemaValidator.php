<?php
namespace freeline\FiscalCore\Support;


final class NFSeSchemaValidator
{
    public function validate(string $xml, string $schemaPath): array
    {
        libxml_use_internal_errors(true);

        $dom = new \DOMDocument();
        $dom->loadXML($xml);

        $valid = $dom->schemaValidate($schemaPath);

        $errors = array_map(
            fn (\LibXMLError $e) => trim($e->message),
            libxml_get_errors()
        );

        libxml_clear_errors();

        return [
            'valid' => $valid,
            'schema' => $schemaPath,
            'errors' => $errors,
        ];
    }
}
