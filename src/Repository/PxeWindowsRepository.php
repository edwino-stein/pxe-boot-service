<?php
namespace App\Repository;

use App\Repository\AbstractPxeTargetRepository;
use App\Model\PxeTargetModel;

class PxeWindowsRepository extends AbstractPxeTargetRepository
{
    protected function setupModel(PxeTargetModel $model) : PxeTargetModel
    {
        $model->setKernel($this->getResourceService()->getTool('wimboot'))
              ->pushInitrd('boot/BCD', ['BCD'])
              ->pushInitrd('boot/boot.sdi', ['boot.sdi'])
              ->pushInitrd('boot/boot.wim', ['boot.wim']);

        return parent::setupModel($model);
    }
}
