<?php

declare(strict_types=1);

namespace PrestaShop\Module\Redirect\Service;

use PrestaShop\Module\Redirect\Enum\HttpRedirectCode;
use PrestaShop\Module\Redirect\Repository\RedirectRuleRepository;

final class CsvImportService
{
    private const DELIMITER = ';';

    public function __construct(
        private readonly RedirectRuleRepository $repository
    ) {
    }

    /**
     * @return array{imported: int, errors: array<int, string>}
     */
    public function import(string $filePath): array
    {
        $handle = fopen($filePath, 'r');

        if ($handle === false) {
            return ['imported' => 0, 'errors' => ['Impossible de lire le fichier.']];
        }

        // Ignore un éventuel BOM UTF-8
        $bom = fread($handle, 3);
        if ($bom !== "\xEF\xBB\xBF") {
            rewind($handle);
        }

        $rows = [];
        $errors = [];
        $lineNumber = 0;

        while (($line = fgetcsv($handle, 0, self::DELIMITER)) !== false) {
            ++$lineNumber;

            if ($line === [null] || $line === false) {
                continue;
            }

            [$source, $destination, $code] = array_pad($line, 3, null);

            if ($lineNumber === 1 && strtolower((string) $source) === 'source') {
                continue;
            }

            $source = trim((string) $source);
            $destination = trim((string) $destination);

            if ($source === '' || $destination === '') {
                $errors[] = sprintf('Ligne %d : source ou destination manquante.', $lineNumber);
                continue;
            }

            $httpCode = (int) ($code ?: HttpRedirectCode::MovedPermanently->value);

            if (null === HttpRedirectCode::tryFrom($httpCode)) {
                $errors[] = sprintf('Ligne %d : code HTTP "%s" invalide.', $lineNumber, (string) $code);
                continue;
            }

            $rows[] = [
                'source' => $source,
                'destination' => $destination,
                'http_code' => $httpCode,
            ];
        }

        fclose($handle);

        $imported = $this->repository->bulkInsertFromCsv($rows);

        return ['imported' => $imported, 'errors' => $errors];
    }
}
