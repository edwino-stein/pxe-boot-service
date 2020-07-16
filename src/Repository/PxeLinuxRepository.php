<?php
namespace App\Repository;

use Symfony\Component\Filesystem\Exception\FileNotFoundException;

use App\Repository\AbstractPxeTargetRepository;
use App\Service\PxeResourceService;
use App\Model\PxeTargetModel;

class PxeLinuxRepository extends AbstractPxeTargetRepository
{
    protected function setupModel(PxeTargetModel $model) : PxeTargetModel
    {
        $bootDir = isset($this->getCfg()['boot']) ? $this->getCfg()['boot'] : 'boot';
        $vmlinu = isset($this->getCfg()['vmlinu']) ? $this->getCfg()['vmlinu'] : 'vmlinu*';
        $initrd = isset($this->getCfg()['initrd']) ? $this->getCfg()['initrd'] : 'initrd*';

        try{
            $vmlinu = $this->findFiles(
                $vmlinu,
                $bootDir,
                $model->getDist(),
                $model->getArch()
            )[0];

            $initrd = $this->findFiles(
                $initrd,
                $bootDir,
                $model->getDist(),
                $model->getArch()
            )[0];
        }
        catch(\Exception $e){
            throw new \RuntimeException(
                'Unable to find linux boot files for "'.$this->getName().
                '/'.$model->getDist().'/'.$model->getArch().'"',
                1,
                $e
            );
        }

        $model->setKernel(PxeResourceService::concatPath([$bootDir, $vmlinu]))
              ->pushInitrd(PxeResourceService::concatPath([$bootDir, $initrd]));

        $args = [
            '{vmlinu}' => $vmlinu,
            '{initrd}' => $initrd,
            '{boot}' => $bootDir,
            '{target}' => $this->name,
            '{dist}' => $model->getDist(),
            '{arch}' => $model->getArch(),
            '{mode}' => $model->getMode()
        ];

        foreach ($this->getCfg()['imgargs'] as $a) {
            $model->pushImgarg(self::encodeStr($a, $args));
        }

        return $model->setLabel(self::encodeStr($this->getLabel(), $args));
    }

    protected function findFiles(string $file, string $bootDir, string $dist, string $arch) : array
    {
        $path = PxeResourceService::concatPath([$dist, $arch, $bootDir]);
        $finder = $this->getResourceService()->getRepositoryFinder($this->getName());
        $finder->path($path)->files()->depth(3)->name($file);

        $files = [];
        foreach ($finder as $f) $files[] = $f->getFileName();

        if(empty($files)){
            throw new FileNotFoundException(null, 0, null, $bootDir.'/'.$file);
        }

        return $files;
    }
}
