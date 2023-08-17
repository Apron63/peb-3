<?php

namespace App\Service;

use App\Entity\Config;
use App\Repository\ConfigRepository;

class ConfigService
{
    public function __construct(
        private readonly ConfigRepository $configRepository,
    ) {}

    public function getConfigValue(string $attribute): mixed
    {
        $config = $this->configRepository->findOneBy([]);

        if(! $config instanceof Config) {
            $config = new Config();

            $this->configRepository->save($config, true);
        }

        $result = null;

        $method = 'get' . ucfirst($attribute);
        if (method_exists($config, $method)) {
            $result = $config->$method();
        }

        return $result;
    }

    public function setConfigValue(string $attribute, $value)
    {
        $config = $this->configRepository->findOneBy([]);

        if(! $config instanceof Config) {
            $config = new Config();

            $this->configRepository->save($config, true);
        }

        $method = 'set' . ucfirst($attribute);
        if (method_exists($config, $method)) {
            $config->$method($value);

            $this->configRepository->save($config, true);
        }
    }
}
