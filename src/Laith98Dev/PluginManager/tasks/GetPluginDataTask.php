<?php

namespace Laith98Dev\PluginManager\tasks;

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

use pocketmine\scheduler\AsyncTask;
use pocketmine\utils\Internet;

class GetPluginDataTask extends AsyncTask {

    const SUCCESS = 0;
    const FAILED = 1;

    const OPTIONS = [
        "ssl" => [
            "verify_peer" => false,
            "verify_peer_name" => false
        ]
    ];

    const POGGIT_PATH = "https://poggit.pmmp.io/releases.json?name=";
    const POGGIT_GET_PATH = "https://poggit.pmmp.io/get/";
    const PLUIN_YAML_DATA = "https://raw.githubusercontent.com/{repo_name}/{commit}/plugin.yml";

    public function __construct(
        private string $pluginName,
        private $callback
    ){
        // NOOP
    }
    
    public function onRun(): void
    {
        $url = self::POGGIT_PATH . $this->pluginName;
        $result = Internet::getURL($url, 15);
        $this->setResult($result?->getBody());
    }

    public function onCompletion(): void
    {
        $results = $this->getResult();
        
        if($results == "[]"){
            ($this->callback)(self::FAILED, [], "Plugin Not Found!");
        } elseif(is_array(($data = json_decode($results, true)))) {
            ($this->callback)(self::SUCCESS, $data);
        } else {
            ($this->callback)(self::FAILED, [], "An error occured while accessing the Poggit API!");
        }
    }
}