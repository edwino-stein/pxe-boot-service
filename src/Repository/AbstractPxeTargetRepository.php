<?php
namespace App\Repository;

use App\Service\PxeResourceService;

abstract class AbstractPxeTargetRepository
{
    protected $pxe = null;
    protected $name = '';
    protected $cfg = null;

    protected function onSetup() : void {}

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
