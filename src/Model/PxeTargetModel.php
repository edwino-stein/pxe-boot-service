<?php
namespace App\Model;

use App\Repository\AbstractPxeTargetRepository;

class PxeTargetModel
{
    protected $dist = '';
    protected $arch = '';
    protected $mode = '';
    protected $label = '';
    protected $kernel = '';
    protected $initrd = [];
    protected $imgargs = [];

    protected $repository = null;

    public function __construct(
        string $dist,
        string $arch,
        string $mode,
        AbstractPxeTargetRepository $repository
    ){
        $this->dist = $dist;
        $this->arch = $arch;
        $this->mode = $mode;
        $this->repository = $repository;
    }

    /**
     * Get the value of Kernel
     * @return string
     */
    public function getKernel() : string { return $this->kernel; }

    /**
     * Set the value of Kernel
     *
     * @param string $kernel
     *
     * @return self
     */
    public function setKernel(string $kernel) : self
    {
        $this->kernel = $kernel;
        return $this;
    }

    /**
     * Get the value of Initrd
     * @return array
     */
    public function getInitrd() : array { return $this->initrd; }

    /**
     * Push a initrd to the boot setup
     * @return self
     */
    public function pushInitrd(string $initrd, array $options = []) : self
    {
        $this->initrd[$initrd] = $options;
        return $this;
    }

    /**
     * Get the value of Imgargs
     * @return array
     */
    public function getImgargs() : array { return $this->imgargs; }

    /**
     * Push a image argument to the boot setup
     * @return self
     */
    public function pushImgarg(string $value, string $key = '') : self
    {
        if($key != '') $this->imgargs[$key] = $value;
        else $this->imgargs[] = $value;
        return $this;
    }

    /**
     * Get the value of Label
     * @return string
     */
    public function getLabel
    () : string { return $this->label; }

    /**
     * Set a label to the boot entry menu
     * @return self
     */
    public function setLabel(string $label) : self
    {
        $this->label = $label;
        return $this;
    }

    /**
     * Get the value of Dist
     * @return string
     */
    public function getDist() : string
    {
        return $this->dist;
    }

    /**
     * Get the value of Arch
     * @return string
     */
    public function getArch() : string
    {
        return $this->arch;
    }

    /**
     * Get the value of Mode
     * @return string
     */
    public function getMode() : string
    {
        return $this->mode;
    }
}
