<?php
namespace App\Repository;

use App\Service\PxeResourceService;
use App\Model\PxeTargetModel;

abstract class AbstractPxeTargetRepository
{
    protected $pxe = null;
    protected $name = '';
    protected $cfg = null;

    protected function createModel(string $dist, string $arch, string $mode) : PxeTargetModel
    { return $this->setupModel(new PxeTargetModel($dist, $arch, $mode, $this)); }

    protected function setupModel(PxeTargetModel $model) : PxeTargetModel
    {
        return $model->setLabel(self::encodeStr(
            $this->getLabel(),
            [
                '{target}' => $this->name,
                '{dist}' => $model->getDist(),
                '{arch}' => $model->getArch(),
                '{mode}' => $model->getMode()
            ]
        ));
    }

    protected function onSetup() : void {}

    public function fetchAll() : array
    {
        $targets = [];
        $dists = $this->getDists();

        foreach ($dists as $d) {
            $archs = $this->getArchs($d);
            foreach ($archs as $a) {
                $modes = $this->getBootModes($d, $a);
                foreach ($modes as $m) $targets[] = $this->createModel($d, $a, $m);
            }
        }

        return $targets;
    }

    public function findByDistAndMode(string $dist, string $mode) : array
    {
        $targets = [];
        $archs = $this->getArchs($dist);
        foreach ($archs as $a){
            if(!$this->supportsBootMode($mode, $dist, $a)) continue;
            $targets[] = $this->createModel($dist, $a, $mode);
        }
        return $targets;
    }

    public function getDists() : array
    {
        $finder = $this->getResourceService()->getRepositoryFinder($this->getName());
        $finder->directories()->depth(0)->sortByName()->reverseSorting();

        $dists = [];
        foreach ($finder as $d) $dists[] = $d->getFileName();

        if(empty($dists)){
            throw new \RuntimeException(
                'No distributions found for "'.$this->getName().'" repository'
            );
        }

        return $dists;
    }

    public function getArchs(string $dist): array
    {
        $validArchs = PxeResourceService::validArchs();
        $finder = $this->getResourceService()->getRepositoryFinder($this->getName());
        $finder->path($dist)->directories()->depth(1)->sortByName();

        $archs = [];
        foreach ($finder as $a){
            if(in_array($a->getFileName(), $validArchs)) $archs[] = $a->getFileName();
        }

        if(empty($archs)){
            throw new \RuntimeException(
                'No architectures found for "'.$this->getName().'/'.$dist.'"'
            );
        }

        return $archs;
    }

    public function getBootModes(string $dist, string $arch): array
    {
        try{
            $archs = $this->getArchs($dist);
            if(!in_array($arch, $archs)){
                throw new \RuntimeException(
                    'Invalid architecture for "'.$this->getName().'/'.$dist.'/'.$arch.'"'
                );
            }
        }
        catch(\Exception $e){
            throw new \RuntimeException(
                'No boot mode for "'.$this->getName().'/'.$dist.'/'.$arch.'"',
                0,
                $e
            );
        }

        $modes = PxeResourceService::validBootModes();

        if(($arch != 'amd64') && ($key = array_search('efi', $modes) !== false)){
            unset($modes[$key]);
        }

        return $modes;
    }

    public function supportsBootMode(string $mode, string $dist, string $arch) : bool
    { return in_array($mode, $this->getBootModes($dist, $arch)); }

    public function getResourceService() : PxeResourceService
    { return $this->pxe; }

    public function getName(): string
    { return $this->name; }

    public function getCfg(): array
    { return $this->cfg; }

    public function getLabel(): string
    {
        if(isset($this->getCfg()['label'])) return $this->getCfg()['label'];
        else return '{target} {dist} boot ({arch})';
    }

    protected function fileExists(string $file, string $dist, string $arch): bool
    {
        return $this->pxe->fileExistsInRepository(
            PxeResourceService::concatPath([$dist, $arch, $file]),
            $this->getName()
        );
    }

    protected static function encodeStr(string $str, array $args): string
    {
        return str_replace(
            array_keys($args),
            array_values($args),
            $str
        );
    }

    public static function create(
        string $name,
        string $class,
        array $cfg,
        PxeResourceService $pxe
    ) : AbstractPxeTargetRepository
    {
        $repository = new $class();

        $repository->pxe = $pxe;
        $repository->name = $name;
        $repository->cfg = $cfg;
        $repository->onSetup();

        return $repository;
    }
}
