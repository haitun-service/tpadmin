<?php
namespace Haitun\Service\TpAdmin\Option;

/**
 * 接口
 */
interface Option
{

    /**
     * Option constructor.
     * @param string $data
     */
    public function __construct($data);

    /**
     * @return string
     */
    public function getType();

    /**
     * @return string
     */
    public function getData();

    /**
     * @return array
     */
    public function getKeys();

    /**
     * @return array
     */
    public function getValues();

    /**
     * @return array
     */
    public function getKeyValues();



}