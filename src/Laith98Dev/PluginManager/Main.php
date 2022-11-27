<?php

namespace Laith98Dev\PluginManager;

/*  
 *  A plugin for PocketMine-MP.
 *  
 *	 _           _ _   _    ___   ___  _____             
 *	| |         (_) | | |  / _ \ / _ \|  __ \            
 *	| |     __ _ _| |_| |_| (_) | (_) | |  | | _____   __
 *	| |    / _` | | __| '_ \__, |> _ <| |  | |/ _ \ \ / /
 *	| |___| (_| | | |_| | | |/ /| (_) | |__| |  __/\ V / 
 *	|______\__,_|_|\__|_| |_/_/  \___/|_____/ \___| \_/  
 *	
 *	Copyright (c) Laith98Dev
 *  
 *	Youtube: Laith Youtuber
 *	Discord: Laith98Dev#0695
 *	Gihhub: Laith98Dev
 *	Email: spt.laithdev@gamil.com
 *	Donate: https://paypal.me/Laith113
 *
 *	This program is free software: you can redistribute it and/or modify
 *	it under the terms of the GNU General Public License as published by
 *	the Free Software Foundation, either version 3 of the License, or
 *	(at your option) any later version.
 *
 *	This program is distributed in the hope that it will be useful,
 *	but WITHOUT ANY WARRANTY; without even the implied warranty of
 *	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *	GNU General Public License for more details.
 *
 *	You should have received a copy of the GNU General Public License
 *	along with this program.  If not, see <http://www.gnu.org/licenses/>.
 * 	
 */

use Laith98Dev\PluginManager\commands\PluginCommand;
use Laith98Dev\PluginManager\tasks\GetPluginDataTask;
use Laith98Dev\PluginManager\tasks\RemovePluginTask;
use Vecnavium\FormsUI\SimpleForm;
use Vecnavium\FormsUI\CustomForm;
use Vecnavium\FormsUI\ModalForm;
use pocketmine\player\Player;
use pocketmine\plugin\Plugin;
use pocketmine\plugin\PluginBase;
use pocketmine\scheduler\ClosureTask;
use pocketmine\utils\Filesystem;
use pocketmine\utils\TextFormat;

final class Main extends PluginBase {

    public function onEnable(): void{
        $this->getServer()->getCommandMap()->register($this->getName(), new PluginCommand($this));
    }

    public function OpenMainForm(Player $player){
        $form = new SimpleForm(fn(Player $player, ?int $data = null) => match ($data)
        {
            0 => $this->OpenPluginsForm($player),
            1 => $this->OpenPoggitForm($player),
            default => null
        });

        $form->setTitle("PluginManager Form");

        $form->addButton("Plugins List");
        $form->addButton("Poggit Downloader");

        $player->sendForm($form);
    }

    private function OpenPluginsForm(Player $player){
        $manager = $this->getServer()->getPluginManager();
        $plugins = array_values($manager->getPlugins());

        $form = new SimpleForm(function (Player $player, ?int $data = null) use ($plugins){
            if($data === null || count($plugins) === 0)
                return false;
            
            if(isset($plugins[$data])){
                $plugin = $plugins[$data];
                $this->OpenPluginManageForm($player, $plugin);
            }
        });

        $form->setTitle("Plugins List");

        if(count($plugins) === 0){
            $form->setContent("You don't have any plugin yet.");
            $form->addButton("Okay");
        } else {
            foreach ($plugins as $plugin){
                $form->addButton($plugin->getName());
            }
        }

        $player->sendForm($form);
    }

    private function OpenPluginManageForm(Player $player, Plugin $plugin){
        $form = new SimpleForm(fn(Player $player, ?int $data = null) => match ($data)
        {
            0 => $this->getScheduler()->scheduleDelayedTask(new RemovePluginTask($this, $plugin, $player), 20),
            1 => $this->OpenPluginsForm($player),
            default => null
        });

        $form->setTitle("Plugin Manager");

        $line = "\n";
        $space = str_repeat(" ", 5);
        $white = TextFormat::WHITE;
        $green = TextFormat::GREEN;
        $authors = "Unnamed";

        if(count($plugin->getDescription()->getAuthors()) > 0){
            $authors = implode(", ", $plugin->getDescription()->getAuthors());
        }

        $version = $plugin->getDescription()->getVersion();
        $description = strlen($plugin->getDescription()->getDescription()) == 0 ? "No Description" : $plugin->getDescription()->getDescription();
        $form->setContent(
            $line . $space . $white . "- Name: " . $green . $plugin->getName() .
            $line . $space . $white . "- Author(s): " . $green . $authors .
            $line . $space . $white . "- Version: " . $green . $version .
            $line . $space . $white . "- Description: " . $green . $description .
            $line . $space
        );

        $form->addButton("Remove");
        $form->addButton("Back");

        $player->sendForm($form);
    }
    
    private function OpenPoggitForm(Player $player, ?string $error = null){
        $form = new CustomForm(function (Player $player, $data = null){
            if($data === null)
                return false;
            
            $name = null;

            if(isset($data[1]) && $data[1] !== null && $data[1] !== "")
                $name = strval($data[1]);
            
            if($name === null){
                $this->OpenPoggitForm($player, "Please enter a valid plugin name!");
                return false;
            }

            $plugins = array_map("strtolower", array_keys($this->getServer()->getPluginManager()->getPlugins()));
            if(in_array(strtolower($name), $plugins, true)){
                $this->OpenPoggitForm($player, "You've already installed this plugin!");
                return false;
            }

            $this->getServer()->getAsyncPool()->submitTask(new GetPluginDataTask($name, function (int $results, array $data, ?string $error = null) use ($player, $name){
                switch ($results){
                    case GetPluginDataTask::SUCCESS:
                        $form = new SimpleForm(function (Player $player, ?int $data_ = null) use ($name){
                            if($data_ === null)
                                return false;
                            
                            switch ($data_){
                                case 0:
                                    $path = $this->getServer()->getDataPath() . "plugins/" . $name . ".phar";

                                    Filesystem::safeFilePutContents($path, 
                                        file_get_contents(GetPluginDataTask::POGGIT_GET_PATH . $name,
                                        false,
                                        stream_context_create(GetPluginDataTask::OPTIONS)
                                    ));

                                    assert(is_file($path), "There is an error while trying to download the plugin!");

                                    $player->sendMessage(TextFormat::YELLOW . "Plugin downloaded successfully!");

                                    $form = new ModalForm(fn (Player $player, $data = null) => match ($data)
                                    {
                                        true => (function () use ($player){
                                            $player->sendMessage(TextFormat::GREEN . "The server will restart after 5 seconds");
                                            $this->getScheduler()->scheduleDelayedTask(new ClosureTask(fn() => $this->getServer()->shutdown()), 5 * 20);
                                        })(),
                                        default => null
                                    });

                                    $form->setTitle("Restart Form");

                                    $form->setContent("Download Successfully!\n restart the server now?");

                                    $form->setButton1("Yes");
                                    $form->setButton2("Maybe later");

                                    $player->sendForm($form);

                                    break;
                                case 1:
                                    $this->OpenPoggitForm($player);
                                    break;
                            }
                        });

                        $form->setTitle($name . " Data");

                        $line = "\n";
                        $space = str_repeat(" ", 5);
                        $white = TextFormat::WHITE;
                        $green = TextFormat::GREEN;

                        $authors = "Unnamed";
                        $description = "No Description";
                        $data = $data[0];
                        try {
                            $ymlData = file_get_contents(str_replace([
                                "{commit}",
                                "{repo_name}"
                            ], [
                                $data["build_commit"],
                                $data["repo_name"]
                            ], GetPluginDataTask::PLUIN_YAML_DATA), false, stream_context_create(GetPluginDataTask::OPTIONS));

                            if(($yaml = yaml_parse($ymlData)) !== false){
                                if(isset($yaml["description"]) && strlen($yaml["description"]) > 0){
                                    $description = $yaml["description"]; 
                                }
    
                                if(isset($yaml["author"]) && strlen($yaml["author"]) > 0){
                                    $authors = strval($yaml["author"]);
                                }
    
                                if(isset($yaml["authors"]) && (is_array($yaml["authors"]) && !empty($yaml["authors"]))){
                                    $authors = implode(", ", $yaml["authors"]);
                                }
                            }
                        } catch (\Exception $e) {
                            $this->getLogger()->error($e->getMessage());
                        }

                        $form->setContent(
                            $line . $space . $white . "- Name: " . $green . $data["name"] .
                            $line . $space . $white . "- Version: " . $green . $data["version"] .
                            $line . $space . $white . "- Author(s): " . $green . $authors .
                            $line . $space . $white . "- Downloads: " . $green . $data["downloads"] .
                            $line . $space . $white . "- License: " . $green . $data["license"] .
                            $line . $space . $white . "- State: " . $green . $data["state_name"] .
                            $line . $space . $white . "- Description: " . $green . $description .
                            $line . $space
                        );

                        $form->addButton("Download");
                        $form->addButton("Back");

                        $player->sendForm($form);
                        break;
                    case GetPluginDataTask::FAILED:
                        $this->OpenPoggitForm($player, $error);
                        break;
                }
            }));
        });

        $form->setTitle("Download Plugin");

        $form->addLabel($error ?? "Insert the plugin name here to download it");
        $form->addInput("Name", "", "");

        $player->sendForm($form);
    }
}