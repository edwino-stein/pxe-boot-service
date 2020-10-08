<?php
namespace App\Repository;

use App\Repository\AbstractPxeTargetRepository;
use App\Model\PxeTargetModel;

class PxeWindowsRepository extends AbstractPxeTargetRepository
{
    protected $withNetinstall = false;
    protected $netInstallConfigRoute = '/windows/{dist}/{arch}/netinstall.json';
    protected $netInstallSupport = ['10', '8'];

    protected function setupModel(PxeTargetModel $model) : PxeTargetModel
    {
        if($this->withNetinstall && in_array($model->getDist(), $this->netInstallSupport)){
            $model->pushInitrd(
                self::encodeStr(
                    $this->netInstallConfigRoute,
                    [
                        '{dist}' => $model->getDist(),
                        '{arch}' => $model->getArch()
                    ]
                ),
                ['netinstall.json']
            );
        }

        $model->setKernel($this->getResourceService()->getTool('wimboot'))
              ->pushInitrd('boot/BCD', ['BCD'])
              ->pushInitrd('boot/boot.sdi', ['boot.sdi'])
              ->pushInitrd('boot/boot.wim', ['boot.wim']);

        return parent::setupModel($model);
    }

    public function getDists() : array
    {
        return array_reverse(parent::getDists());
    }

    protected function onSetup() : void
    {
        if(isset($this->getCfg()['netinstall']) && is_bool($this->getCfg()['netinstall'])){
            $this->withNetinstall = $this->getCfg()['netinstall'];
        }
    }
}
