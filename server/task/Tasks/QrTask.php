<?php

namespace Selpol\Task\Tasks;

use backends\files\files;
use Exception;
use PhpOffice\PhpWord\Settings;
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
        $file = tempnam(Settings::getTempDir(), 'qr-zip');
        $files = [];

        try {
            $zip = new ZipArchive();
            $zip->open($file, ZipArchive::OVERWRITE);

            foreach ($qr['flats'] as $flat) {
                $template = new TemplateProcessor(path('private/qr-template.docx'));

                $template->setValue('address', $qr['address'] . ', кв' . $flat['flat']);

                $templateFile = $template->save();
                $files[] = $templateFile;

                $zip->addFile($templateFile);
            }

            echo json_encode($zip->count());
            $zip->close();

            return $this->files->addFile($qr['address'] . ' QR.zip', fopen($file, "r"));
        } catch (Exception) {
            return null;
        } finally {
            //unlink($file);

            foreach ($files as $file)
                unlink($file);
        }
    }

    private function getCode(int $flatId): string
    {
        return $this->houseId . '-' . $flatId . '-' . md5(guid_v4());
    }
}