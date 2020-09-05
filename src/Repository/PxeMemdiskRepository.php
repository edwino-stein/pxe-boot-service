<?php
namespace App\Repository;

use App\Repository\AbstractPxeTargetRepository;
use App\Model\PxeTargetModel;

class PxeMemdiskRepository extends AbstractPxeTargetRepository
{
    protected function setupModel(PxeTargetModel $model) : PxeTargetModel
    {
        $isoName = isset($this->getCfg()['iso']) ?  $this->getCfg()['iso'] : 'boot.img';
        if(!$this->fileExists($isoName, $model->getDist(), $model->getArch())){
            throw new \RuntimeException(
                'Unable to find boot image file of "'.$this->getName().
                '/'.$model->getDist().'/'.$model->getArch().'"',
                1
            );
        }

        $model->setKernel($this->getResourceService()->getTool('memdisk'))
              ->pushInitrd($isoName);

          $args = [
              '{target}' => $this->name,
              '{dist}' => $model->getDist(),
              '{arch}' => $model->getArch(),
              '{mode}' => $model->getMode()
          ];

          foreach ($this->getCfg()['imgargs'] as $a) {
              $model->pushImgarg(self::encodeStr($a, $args));
          }

        return parent::setupModel($model);
    }

    public function getBootModes(string $dist, string $arch): array
    { return [ 'bios' ]; }
}
