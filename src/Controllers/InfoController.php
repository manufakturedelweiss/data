<?php
/** Freesewing\Data\Controllers\CoreController class */
namespace Freesewing\Data\Controllers;

use Symfony\Component\Yaml\Yaml;
use GuzzleHttp\Client as GuzzleClient;
use \Freesewing\Data\Tools\Utilities as Utilities;

/**
 * Pulls data from freesewing core info service and bundles it 
 *
 * @author Joost De Cock <joost@decock.org>
 * @copyright 2017 Joost De Cock
 * @license http://opensource.org/licenses/GPL-3.0 GNU General Public License, Version 3
 */
class InfoController 
{
    protected $container;

    // constructor receives container instance
    public function __construct(\Slim\Container $container) {
        $this->container = $container;
    }

    /** Info bundle as YAML */
    public function asYaml($request, $response, $args) 
    {
        return $response
            ->withHeader('Access-Control-Allow-Origin', $this->container['settings']['app']['origin'])
            ->withHeader("Content-Type", "text/plain")
            ->write(Yaml::dump($this->infoBundle(),5));
    }

    /** Info bundle as JSON */
    public function asJson($request, $response, $args) 
    {
        return $response
            ->withHeader('Access-Control-Allow-Origin', $this->container['settings']['app']['origin'])
            ->withHeader("Content-Type", "text/plain")
            ->write(json_encode($this->infoBundle(), JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
    }

    /** Info bundle */
    private function infoBundle() 
    {
        // Get data from info service
        $coreinfo = json_decode(file_get_contents($this->container['settings']['app']['core_api'].'/index.php?service=info'));
        $info['version']['core'] = $coreinfo->version;

        // Iterate over patterns to get remaining info
        $patternlist = $coreinfo->patterns;
        $female = $this->container['settings']['app']['female_measurements'];
        //$male = $this->container['settings']['app']['female_measurements'];
        $measurementTitles = $this->container['settings']['app']['female_measurements'];
        
        foreach ($patternlist as $namespace => $list) {
            foreach ($list as $handle => $title) {
                $patternInfo = json_decode(file_get_contents($this->container['settings']['app']['core_api'].'/index.php?service=info&pattern='.$handle));
                $info['patterns'][$handle] = $this->patternToArray($patternInfo);
                $info['namespaces'][$namespace][] = $patternInfo->info->handle;
                $info['mapping']['handleToPatternTitle'][$patternInfo->info->handle] = $patternInfo->info->name;
                $info['mapping']['handleToPattern'][$patternInfo->info->handle] = $handle;
                $info['mapping']['patternToHandle'][$handle] = $patternInfo->info->handle;
                $info['mapping']['handleToNamespace'][$patternInfo->info->handle] = $namespace;
                foreach ($patternInfo->measurements as $name => $default) {
                    if(in_array($name, $female)) $info['measurements'][$name] = 'female';
                    // else if (in_array($name, $male)) $info['measurements'][$name] = 'male';
                    else  $info['measurements'][$name] = 'all';
                }
            }
        }
        $info['mapping']['measurementToTitle'] =  $this->container['settings']['measurements'];

        // Sort measurements
        ksort($info['measurements']);
        
        return $info;
    }

    private function patternToArray($pattern)
    {
        unset($pattern->models);
        unset($pattern->pattern);

        
        foreach($pattern as $key => $val) {
            if($key == 'options') {
                foreach($val as $okey => $oval) {
                    $options[$okey] = (array) $oval;
                    $ogroups[$oval->group][] = $okey;
                }
                $p['options'] = $options;
                $p['optiongroups'] = $ogroups;
                unset($options);
                unset($ogroups);
            }
            elseif($key == 'info') {
                $p[$key] = (array) $val;
                // Convert inMemoryOf to array
                if(isset($p[$key]['inMemoryOf'])) $p[$key]['inMemoryOf'] = (array) $p[$key]['inMemoryOf'];
            }
            else $p[$key] = (array) $val;
        }
        
        return $p;
    }
    
    /** Status info */
    public function status($request, $response, $args) 
    {
        $memory = $this->asScrubbedArray(rtrim(shell_exec("free -m | grep Mem")));
        $status['system']['memory']['used'] = $memory[2];
        $status['system']['memory']['free'] = $memory[3];
        $swap = $this->asScrubbedArray(rtrim(shell_exec("free -m | grep Swap")));
        $status['system']['swap']['used'] = $swap[2];
        $status['system']['swap']['free'] = $swap[3];
        $stats = rtrim(shell_exec("mpstat 1 1 | tail -n 2 | head -n 1"));
        $stats = explode('  ',strrev($stats));
        $idle = strrev(array_shift($stats));
        $status['system']['cpu'] = 100 - $idle;

        $status['system']['uptime'] = rtrim(substr(shell_exec("uptime -p"), 3));
        $status['data']['users'] = $this->countUsers(); 
        $status['data']['drafts'] = $this->countDrafts(); 
        $status['data']['comments'] = $this->countComments(); 
        $status['data']['models'] = $this->countModels(); 

        return $response
            ->withHeader('Access-Control-Allow-Origin', $this->container['settings']['app']['origin'])
            ->withHeader("Content-Type", "application/json")
            ->write(json_encode($status, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
    }

    private static function asScrubbedArray($data, $separator = ' ')
    {
        $return = false;
        $array = explode($separator, $data);
        foreach ($array as $value) {
            if (rtrim($value) != '') {
                $return[] = rtrim($value);
            }
        }

        return $return;
    }

    private function countUsers()
    {
        $db = $this->container->get('db');
        $sql = "SELECT COUNT(id) as 'users' FROM `users` WHERE `status` = 'active'";
        $result = $db->query($sql)->fetchAll(\PDO::FETCH_OBJ);
        $db = null;

        return $result[0]->users;
    }

    private function countDrafts()
    {
        $db = $this->container->get('db');
        $sql = "SELECT COUNT(id) as 'drafts' FROM `drafts`";
        $result = $db->query($sql)->fetchAll(\PDO::FETCH_OBJ);
        $db = null;

        return $result[0]->drafts;
    }
    
    private function countComments()
    {
        $db = $this->container->get('db');
        $sql = "SELECT COUNT(id) as 'comments' FROM `comments`";
        $result = $db->query($sql)->fetchAll(\PDO::FETCH_OBJ);
        $db = null;

        return $result[0]->comments;
    }
    
    private function countModels()
    {
        $db = $this->container->get('db');
        $sql = "SELECT COUNT(id) as 'models' FROM `models`";
        $result = $db->query($sql)->fetchAll(\PDO::FETCH_OBJ);
        $db = null;

        return $result[0]->models;
    }
}
