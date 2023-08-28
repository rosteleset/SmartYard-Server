<?php

namespace Selpol\Task\Tasks;

use backends\files\files;
use Exception;
use PhpOffice\PhpWord\TemplateProcessor;
use Selpol\Task\Task;
use ZipArchive;

class QrTask extends Task
{
    public int $houseId;
    public ?int $flatId;

    private files $files;

    public function __construct(int $houseId, ?int $flatId)
    {
        parent::__construct('Qr (' . $houseId . ', ' . ($flatId ?? -1) . ')');

        $this->houseId = $houseId;
    }

    public function onTask(): ?string
    {
        $this->files = loadBackend('files');

        $house = loadBackend('addresses')->getHouse($this->houseId);

        $qr = $this->getOrCreateQr($house);

        $this->setProgress(25);

        return $this->createQrZip($qr);
    }

    private function getOrCreateQr(array $house): array
    {
        $households = loadBackend('households');

        $flats = $households->getFlats('houseId', $this->houseId); // code

        $result = ['address' => $house['houseFull'], 'flats' => []];

        foreach ($flats as $flat) {
            if (!isset($flat['code']) || is_null($flat['code']) || $flat['code'] == '') {
                $code = $this->getCode($flat['flatId']);

                $flat['code'] = $code;

                $households->modifyFlat($flat['flatId'], ['code' => $code]);
            }

            $result['flats'][] = ['flat' => $flat['flat'], 'code' => $flat['code']];
        }

        return $result;
    }

    private function createQrZip(array $qr): ?string
    {
        $file = tempnam('tmp', 'qr-zip');

        try {
            $zip = new ZipArchive();
            $zip->open($file, ZipArchive::OVERWRITE);

            foreach ($qr['flats'] as $flat) {
                $template = new TemplateProcessor(path('.docx'));

                $template->setValue('address', $qr['address'] . ', кв' . $flat['flat']);

                $templateFile = $template->save();

                $zip->addFile($templateFile);

                unlink($templateFile);
            }

            $zip->close();

            return $this->files->addFile($qr['address'] . ' QR.zip', fopen($file, "r"));
        } catch (Exception) {
            return null;
        } finally {
            unlink($file);
        }
    }

    private function getCode(int $flatId): string
    {
        return $this->houseId . '-' . $flatId . '-' . md5(guid_v4());
    }
}