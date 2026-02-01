<?php

namespace chainsaw\cps;

use chainsaw\cps\listeners\CPSListener;
use chainsaw\cps\commands\CPSCommand;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\Config;

class Main extends PluginBase
{
    private static ?Main $instance = null;
    private Config $playerSettings;
    private CPSListener $cpsListener;

    public function onEnable(): void {
        self::$instance = $this;

        $this->saveDefaultConfig();
        @mkdir($this->getDataFolder());
        $this->playerSettings = new Config($this->getDataFolder() . "players.yml", Config::YAML);

        $this->cpsListener = new CPSListener($this);
        $this->getServer()->getPluginManager()->registerEvents($this->cpsListener, $this);
        $this->getServer()->getCommandMap()->register("cps", new CPSCommand($this));
    }

    public function onDisable(): void {
        $this->playerSettings->save();
    }

    public static function getInstance(): ?Main {
        return self::$instance;
    }

    public function getPlayerSettings(): Config {
        return $this->playerSettings;
    }

    public function getCPSListener(): CPSListener {
        return $this->cpsListener;
    }

    public function getPlayerSetting(string $playerName, string $setting, mixed $default = null): mixed {
        return $this->playerSettings->getNested("{$playerName}.{$setting}", $default);
    }

    public function setPlayerSetting(string $playerName, string $setting, mixed $value): void {
        $this->playerSettings->setNested("{$playerName}.{$setting}", $value);
        $this->playerSettings->save();
    }
}