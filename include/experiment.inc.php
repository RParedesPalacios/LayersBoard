<?php

/*
**********************************************************************
* experiment.inc.php
**********************************************************************
*
* Changelog:
*     18/01/17 - Primera versión (Tonny)
*
**********************************************************************
*/

require_once('database.inc.php');
require_once('functions.inc.php');

///////////////////////////////////////////////////////////////////////
// Experiment
// Class to manage experiment-related data. Easily allows to manipulate some
// of the basic data of a experiment (query related listing, insert, etc).
///////////////////////////////////////////////////////////////////////

class Experiment
{
    // -------------------------------
    // Members
    // -------------------------------
    public $id                      = 0;
    public $name                    = "";
    public $user_id                 = -1;
    public $const_threads           = -1;
    public $const_batch             = -1;
    public $const_log_filename      = "netlog.log";
    public $const_seed              = -1;
    public $network_raw             = "network N1{}";
    public $script_raw              = "script{}";
    public $best_result_test        = -1.0;
    public $best_result_train       = -1.0;
    public $epoc_best_result_test   = -1;
    public $epoc_best_result_train  = -1;
    public $dataset_id              = -1;
    public $process_id              = -1;

    public $total_epocs             = -1;
    public $current_epocs           = -1;

    public $is_finished             = 0;
    public $is_running              = 0;

    
    private $conn = null;
       
    // -------------------------------
    // Constructor
    // -------------------------------
    public function __construct($id = -1)
    {
        // Get the db connection link to use into this scope as private attribute of the class
        global $mysqli;

        $this->conn = $mysqli;

        if (is_int($id) && $id != -1)
        {
            $this->FetchByID($id);
        }
        else
        {
            $this->FetchByObj($id);
        }
    }

    // -------------------------------
    // Methods
    // -------------------------------

    // --- Loading methods ---

    /*
    ** Fetches the experiment data of the given experiment ID.
    ** @param $id            experiment ID to fetch.
    ** @throws               Exception if the task is invalid or the query failed.
    */
    public function FetchByID($id)
    {
        global $user_id;

        $toReturn = null;

        $query = "SELECT * FROM `experiment` WHERE `id`=$id and `user_id`=$user_id";

        // Validate results
        if ($result = $this->conn->query($query)) 
        {
            // Retreive results.
            if($data = $result->fetch_object())
            {
                $this->id                      = $data->id;
                $this->name                    = $data->name;
                $this->user_id                 = $data->user_id;
                $this->const_threads           = $data->const_threads;
                $this->const_batch             = $data->const_batch;
                $this->const_log_filename      = $data->const_log_filename;
                $this->const_seed              = $data->const_seed;
                $this->network_raw             = $data->network_raw;
                $this->script_raw              = $data->script_raw;
                $this->best_result_test        = $data->best_result_test;
                $this->best_result_train       = $data->best_result_train;
                $this->epoc_best_result_test   = $data->epoc_best_result_test;
                $this->epoc_best_result_train  = $data->epoc_best_result_train;
                $this->dataset_id              = $data->dataset_id;
                $this->process_id              = $data->process_id;

                $toReturn = $this;
            }
            else
            {
                die('<div class="alert alert-danger"><a class="close" data-dismiss="alert" href="#">&times;</a><p style="text-align:center">Experiment not found</p></div>');
            }
        }

        return $toReturn;
    }

    public function FetchByObj($data)
    {
        $this->id                      = $data->id;
        $this->name                    = $data->name;
        $this->user_id                 = $data->user_id;
        $this->const_threads           = $data->const_threads;
        $this->const_batch             = $data->const_batch;
        $this->const_log_filename      = $data->const_log_filename;
        $this->const_seed              = $data->const_seed;
        $this->network_raw             = $data->network_raw;
        $this->script_raw              = $data->script_raw;
        $this->best_result_test        = $data->best_result_test;
        $this->best_result_train       = $data->best_result_train;
        $this->epoc_best_result_test   = $data->epoc_best_result_test;
        $this->epoc_best_result_train  = $data->epoc_best_result_train;
        $this->dataset_id              = $data->dataset_id;
        $this->process_id              = $data->process_id;

        $this->GetJsonFromLog();
    }
    /*
    ** Inserts a new experiment
    */
    public static function CreateExperiment($name, $user_id, $const_threads, $const_batch, $const_seed, $network_raw, $script_raw, $dataset_id, $netfile)
    {

        global $mysqli;
        global $layersBoardPath;

        $toReturn = false;
        
        // Clean string vars
        $name               = CleanVar($name);
        $user_id            = (int) $user_id;
        $const_threads      = (int) $const_threads;
        $const_log_filename = "netlog.log";
        $const_seed         = (int) $const_seed;
        $network_raw        = CleanVar($network_raw);
        $script_raw         = CleanVar($script_raw);
        $dataset_id         = (int) $dataset_id;
        
        // Create folder for the experiment in Experiments 
        $experiment_path = $layersBoardPath."/Experiments/".$name;
        mkdir($experiment_path);

        // Write the netfile.net file
        $handle = fopen($experiment_path."/netfile.net", 'w+');
        if($handle)
        {
            if(!fwrite($handle, $netfile)) 
                die("The process of creating a new experiment has failed");
        }

        $query = "INSERT INTO `experiment` (`name`, `user_id`, `const_threads`, `const_batch`, `const_log_filename`, `const_seed`, `network_raw`, `script_raw`, `dataset_id`, `create_date` )
                   VALUES                ('$name', '$user_id', $const_threads,  $const_batch, '$const_log_filename', $const_seed, '$network_raw', '$script_raw', $dataset_id, NOW() )";

        // Check if everything gone ok
        if( $result = $mysqli->query($query) )
        {
            $toReturn = $mysqli->insert_id;
        }

        return $toReturn;    
    }

   /*
    ** Starts running the experiment in Layers
    ** Retrieves the PID to save it into the database
    */
    public function Run()
    {
        global $layersBoardPath;

        // Go to layersBoard path
        chdir($layersBoardPath);

        $experiment_path = $layersBoardPath."/Experiments/".$this->name;

        // Execs layers in background and returns its pid
        //exec("layers ".$experiment_path."netfile.net 2>/dev/null & echo $!", $pid);
        exec("layers ".$experiment_path."/netfile.net > /dev/null 2>/dev/null & echo $!", $pid);

        // After running layers, a #NetName.dot will be generated, we move it to $experiment_path folder
        foreach (glob("*.dot") as $dotfile) {
            rename ($dotfile, $experiment_path.'/'.$dotfile);
        }


        $this->UpdatePID($pid[0]);
    } 

    /*
    ** Assign a given PID to an experiment
    ** (It could be null)
    */
    public function UpdatePID($pid)
    {
        global $user_id;

        $this->process_id = (int) $pid;
        $query = "UPDATE `experiment` SET `process_id` = $this->process_id WHERE `id` = $this->id AND `user_id` = $user_id";

        // Validate results
        $result = $this->conn->query($query);
    }

    /*
    ** Returns true if the process asigned to the experiment (process_id) 
    ** is currently running (and is layers instance)
    */
    public function IsRunning()
    {
        global $user_id;

        $process_id = (int) $this->process_id;

        // Return by default that is not running
        $toReturn = false;

        // If 
        if($this->IsStarted() && $process_id > 0 && $process_id != null)
        {
            // Gets all current pids from layers instances 
            exec("pgrep layers", $pids);

            foreach ($pids as $pid) 
            {
                // Only if the process_id is in the list, return true
                if($pid == $process_id)
                    $toReturn = true;
            }
        }

        return $toReturn;   
    }

    /*
    ** Returns if the process has finished
    */
    public function IsFinished()
    {
        global $user_id;

        // Return by default that is not running
        $toReturn = false;

        // If it started and current_epoc has reached total_epocs
        if($this->IsStarted() && $this->total_epocs > 0 && $this->total_epocs == $this->current_epocs)
        {
            $toReturn = true;
            // Update database PID (remove reference)
            $this->UpdatePID(0);
        }    

        return $toReturn;
    }
    /*
    ** Returns if the process has started (by looking for netlog.log)
    */
    public function IsStarted()
    {
        $logfile = $this->GetExperimentPath()."/netlog.log";

        $toReturn = false;

        // If the netlog.log file is not created, the experiment has not started yet
        if (file_exists($logfile)) 
        {
            $toReturn = true;
        } 

        return $toReturn;
    }

    /*
    ** Returns the working path directory where the experiment is
    */
    private function GetExperimentPath()
    {
        global $layersBoardPath;

        return $layersBoardPath."/Experiments/".$this->name;
    }

    /*
    ** Returns .dot file created by layers. Used to show network topology
    */
    public function GetDotFileContents()
    {
        global $layersBoardPath;

        $experiment_path = $layersBoardPath."/Experiments/".$this->name;

        // After running layers, a #NetName.dot will be generated, we move it to $experiment_path folder
        foreach (glob($experiment_path."/*.dot") as $dotfile) 
        {
            $toReturn = file_get_contents($dotfile, FILE_USE_INCLUDE_PATH);

            return $toReturn;
        }

    }

    // Recursively assigns experiment name
    public static function CheckExperimentName($selectedName)
    {
        global $mysqli;

        $name = CleanVar($selectedName);

        $query = "SELECT * FROM `experiment` WHERE `name` = '$name' ";

        // Validate results
        if ($result = $mysqli->query($query)) 
        {
            if($result->num_rows > 0)
                $returnedName = $selectedName."_".GetRandomCode(5);
            else
                $returnedName = $selectedName;
        }

        if($selectedName != $returnedName)
            return Experiment::CheckExperimentName($returnedName);
        else
            return $returnedName;
    }

    /*
    ** Fetches all experiments.
    ** @param $id            dataset ID to fetch.
    */
    public static function FetchAllFinished()
    {
        global $mysqli;
        global $user_id;

        $toReturn = [];

        $query = "SELECT * FROM `experiment` WHERE `user_id` = $user_id ORDER BY best_result_test ASC";

        // Validate results
        if ($result = $mysqli->query($query)) 
        {
            // Retreive results.
            while ($data = $result->fetch_object()) 
            {
                $experiment = new Experiment($data);

                if($experiment->IsFinished())
                    $experiment->Render();
            }
        }

        return $toReturn;
    }

        /*
    ** Fetches all experiments.
    ** @param $id            dataset ID to fetch.
    */
    public static function FetchAllNotStarted()
    {
        global $mysqli;
        global $user_id;

        $toReturn = [];

        $query = "SELECT * FROM `experiment` WHERE `user_id` = $user_id";

        // Validate results
        if ($result = $mysqli->query($query)) 
        {
            // Retreive results.
            while ($data = $result->fetch_object()) 
            {
                $experiment = new Experiment($data);

                if( ! $experiment->IsStarted() )
                    $experiment->Render();
            }
        }

        return $toReturn;
    }
        /*
    ** Fetches all experiments.
    */
    public static function FetchAllRunning()
    {
        global $mysqli;
        global $user_id;

        $toReturn = [];

        $query = "SELECT * FROM `experiment` WHERE `user_id` = $user_id";

        // Validate results
        if ($result = $mysqli->query($query)) 
        {
            // Retreive results.
            while ($data = $result->fetch_object()) 
            {
                $experiment = new Experiment($data);

                if($experiment->IsRunning())
                    $experiment->Render();
                
            }
        }

        return $toReturn;
    }
    /*
    ** Fetches all experiments ordered by % test error
    */
    public static function FetchAll()
    {
        global $mysqli;
        global $user_id;

        $toReturn = [];

        $query = "SELECT * FROM `experiment` WHERE `user_id` = $user_id ORDER BY `best_result_test` ASC";

        // Validate results
        if ($result = $mysqli->query($query)) 
        {
            // Retreive results.
            while ($data = $result->fetch_object()) 
            {
                $experiment = new Experiment($data);

                if($experiment->IsStarted() && $experiment->current_epocs > 0)
                        $experiment->Render();
                
            }
        }

        return $toReturn;
    }
    public function Render()
    {
    ?>
        <?php
        if(!$this->IsStarted())
        {
            $label = "Stop";
            $class = "danger";
            $percent = 0;
        }
        else 
        {
            if($this->IsFinished())
            {
                $label = "Done";
                $class = "success";
                $percent = 100;
            }
            else
            {
                $label = "Live";
                $class = "primary";
                if($this->current_epocs > 0)
                    $percent = (int) (($this->current_epocs / $this->total_epocs) * 100);
                else
                    $percent = 0;
            }
        }
        ?>
        <a href="index.php?experiment_id=<?=$this->id;?>" class="col-xs-12 col-sm-6 col-md-4 col-lg-3 experiment-short">
            <div class="offer offer-<?=$class;?>">
                <div class="shape">
                    <div class="shape-text"><?=$percent;?> %</div>
                </div>
                <div class="offer-content">
                <div class="clearfix"></div>
                    <h3 class="lead text-center">
                        <?=$this->name;?> 
                    </h3>
                    <h3 class="lead text-center">
                        <?=$this->best_result_test;?>%
                    </h3>
                    <span>% error at test</span>
                    <br/>
                    <span><?=$this->best_result_train;?> % error training</span>
                    <!--<a href=""><?=$this->name;?></a>-->
                </div>
            </div>
        </a>           
    <?php
    } 


    public function CheckIfFinished()
    {

    }

    public function Delete()
    {
        global $layersBoardPath;
        global $user_id;

        $experiment_path = $layersBoardPath."/Experiments/".$this->name;

        // Delete folder
        rrmdir($experiment_path);

        // Delete from database
        $query = "DELETE FROM `experiment` WHERE id=$this->id and user_id=$user_id";

        // Validate results
        if ($result = $this->conn->query($query)) 
        {
            // Return true
            return true;
        }

        return false;
    }

    public function StopProcess()
    {
        global $user_id;

        // Gets all current pids from layers instances 
        exec("kill -9 ".$this->process_id, $return);


        if( !$this->IsRunning() )
        {
            $this->UpdatePID(0);
            return true;
        }
        else
            return false;
    }

    public function RenderTerminalLog()
    {
        global $layersBoardPath;

        $experiment_path = $layersBoardPath."/Experiments/".$this->name;
        $dataset = new Dataset((int) $this->dataset_id);
        $datasetName = $dataset->name;


        $archivoLog = $experiment_path."/netlog.log";
        if(! @$contenido = file_get_contents($archivoLog))
            return;
        $file = fopen($archivoLog, "r") 
                or die('<div class="alert alert-danger"><a class="close" data-dismiss="alert" href="#">&times;</a><p style="text-align:center">Unable to open file!</p></div>');
        //Output a line of the file until the end is reached
        while(!feof($file))
        {
            // We receive something like /home/tonnyesp/LayersBoard/Datasets/MNIST/training Errors (N1:out) 7273 of 60000 12.12% CrossEnt=-0.077709 
            $string = fgets($file);



            // The path should be something like /\/home\/tonnyesp\/LayersBoard\/Datasets\/MNIST\//
            $path = str_replace('/', '\/', $layersBoardPath);
            $path = $path."\/Datasets\/".$datasetName."\//";

            $path = "/".$path;

            // match : ""
            // match : "/\/home\/tonnyesp\/LayersBoard\/Datasets\/MNIST\//"
            $string = preg_replace($path, "", $string);

            echo $string. "<br />";
        }
        fclose($file);
    }

    public function RenderCurrentNet()
    {
        global $layersBoardPath;

        $experiment_path = $layersBoardPath."/Experiments/".$this->name;
        $dataset = new Dataset((int) $this->dataset_id);
        $datasetName = $dataset->name;


        $archivoLog = $experiment_path."/netfile.net";
        @$contenido = file_get_contents($archivoLog);
        $file = fopen($archivoLog, "r") 
                or die('<div class="alert alert-danger"><a class="close" data-dismiss="alert" href="#">&times;</a><p style="text-align:center">Unable to open netfile.net!</p></div>');
        //Output a line of the file until the end is reached
        while(!feof($file))
        {
            $string = fgets($file);

            $string = preg_replace('#^\s*//.+$#m', "----------", $string);
            if($string == "----------\n")
            {
                $string = "";
            }
            if($string == "\n")
                $string = "";


            $string = preg_replace("/^(.*?)[,\t]/", " ", $string);

            echo $string;
        }
        fclose($file);
    }

    private function GetTotalEpocs()
    {
        global $layersBoardPath;

        $experiment_path = $layersBoardPath."/Experiments/".$this->name;
        $dataset = new Dataset((int) $this->dataset_id);
        $datasetName = $dataset->name;


        $archivoLog = $experiment_path."/netfile.net";
        @$contenido = file_get_contents($archivoLog);
        $file = fopen($archivoLog, "r") 
                or die('<div class="alert alert-danger"><a class="close" data-dismiss="alert" href="#">&times;</a><p style="text-align:center">Unable to open netfile.net!</p></div>');

        $toReturn = 0;

        //Output a line of the file until the end is reached
        while(!feof($file))
        {
            $string = fgets($file);
            
            if(preg_match("/train\(([0-9,]+)\)/", $string, $result))
                $toReturn += (int) $result[1];

        }
        fclose($file);
        return $toReturn;
    }


    public function GetJsonFromLog()
    {
        global $layersBoardPath;

        $experiment_path = $layersBoardPath."/Experiments/".$this->name;
        $dataset = new Dataset((int) $this->dataset_id);
        $datasetName = $dataset->name;


        $archivoLog = $experiment_path."/netlog.log";
        if(! @$contenido = file_get_contents($archivoLog))
            return "{}";
        $file = fopen($archivoLog, "r") 
                or die('<div class="alert alert-danger"><a class="close" data-dismiss="alert" href="#">&times;</a><p style="text-align:center">Unable to open file!</p></div>');

        // Stats
        $best_result_train = INF;
        $best_result_test  = INF;
        $epoc_best_result_train = INF;
        $epoc_best_result_test  = INF;

        $total_epocs = $this->GetTotalEpocs();
        $current_epocs = 0;

        $outs = [];

        //Output a line of the file until the end is reached
        while(!feof($file))
        {
            // We receive something like /home/tonnyesp/LayersBoard/Datasets/MNIST/training Errors (N1:out) 7273 of 60000 12.12% CrossEnt=-0.077709
            $string = fgets($file);
            
            // Check if training result or test result
            if(preg_match('/training/',$string))
            {
                $mode = "Training";
                $current_epocs +=1;
            }
            else if(preg_match('/test/',$string))
            {
                $mode = "Test";
            }
            else
            {
                continue;
            }

            // Get the name of the out
            preg_match('/(?<=\()(.+)(?=\))/is', $string, $match_out);
            $out_name = $match_out[1];
            
            // Get the error 
            preg_match("/[\d]+.[\d]+%/", $string, $match_error);
            $error = (double) substr($match_error[0], 0, -1);
            
            // Get the crossentropy
            preg_match('/CrossEnt=([-]?[\d].[\d]+)/', $string, $match_crossent);
            $crossent = (double) $match_crossent[1];

            if(!isset($outs[$out_name]))
            {
                $out =  array(
                    "Training" => [],
                    "Test" => [],
                    "Total_epocs" => $total_epocs,
                    "Current_epocs" => 0,
                    "Best_train_error" => 0,
                    "Best_test_error" => 0,
                    "Epoc_best_train_error" => 0,
                    "Epoc_best_test_error" => 0
                );

                $outs[$out_name] = $out;
            }

            $epoc = array(
                "Error" => $error,
                "CrossEnt" => $crossent
            );

            array_push($outs[$out_name][$mode], $epoc);
            $outs[$out_name]["Current_epocs"] = $current_epocs; 

            if($mode == "Training")
                if($error < $best_result_train)
                {
                    $best_result_train = $error;
                    $epoc_best_result_train = $current_epocs;
                }


            if($mode == "Test")
                if($error < $best_result_test)
                {
                    $best_result_test = $error;
                    $epoc_best_result_test = $current_epocs;
                }

            // when the last iteration
            //if($current_epocs == $total_epocs)
            {
                $outs[$out_name]["Best_train_error"]      = $this->best_result_train      = $best_result_train; 
                $outs[$out_name]["Best_test_error"]       = $this->best_result_test       = $best_result_test; 
                $outs[$out_name]["Epoc_best_train_error"] = $this->epoc_best_result_train = $epoc_best_result_train; 
                $outs[$out_name]["Epoc_best_test_error"]  = $this->epoc_best_result_test  = $epoc_best_result_test; 

                $this->current_epocs = $current_epocs;
                $this->total_epocs   = $total_epocs;
            }
        }

        fclose($file);

        $this->UpdateInDB();

        return json_encode($outs);     
    }

    /*
    ** Updates register in database to match current object
    */
    public function UpdateInDB()
    {
        global $user_id;

        $query = "UPDATE `experiment` SET 
            `name`                   = '$this->name',
            `const_threads`          = $this->const_threads,
            `const_batch`            = $this->const_batch,
            `const_log_filename`     = '$this->const_log_filename',
            `const_seed`             = $this->const_seed,
            `network_raw`            = '$this->network_raw',
            `script_raw`             = '$this->script_raw',
            `best_result_test`       = $this->best_result_test,
            `best_result_train`      = $this->best_result_train,
            `epoc_best_result_test`  = $this->epoc_best_result_test,
            `epoc_best_result_train` = $this->epoc_best_result_train,
            `dataset_id`             = $this->dataset_id,
            `process_id`             = $this->process_id 
        WHERE `id` = $this->id AND `user_id` = $user_id";

        $result = $this->conn->query($query);

    }

    public function FullRender()
    { 
        // Updates information about epocs percent
        $stats = $this->GetJsonFromLog();

        $IsFinished = $this->IsFinished();
        $IsStarted  = $this->IsStarted();
        $IsRunning  = $this->IsRunning();
        // If we don't get the best_result in test for this experiment
        if(!$IsStarted)
        {
            $percent = 0;
        }
        else 
        {
            if($IsFinished)
            {
                $percent = 100;
            }
            else
            {
                if($this->total_epocs > 0)
                    $percent = (int) (($this->current_epocs / $this->total_epocs) * 100);
                else
                    $percent = 0;
            }
        }

        if($IsFinished)
        { ?>
            <div class="alert alert-info">
                <a class="close" data-dismiss="alert" href="#">&times;</a>
                <p style="text-align:center">This experiment has finished already, you can rerun it or fork it </p>
            </div>
        <?php
        }
        if(!$IsStarted)
        { ?>
            <div class="alert alert-warning">
                <a class="close" data-dismiss="alert" href="#">&times;</a>
                <p style="text-align:center">This experiment has not started yet</p>
            </div>
        <?php
        }
        if($IsRunning)
        { ?>
            <div class="alert alert-success">
                <a class="close" data-dismiss="alert" href="#">&times;</a>
                <p style="text-align:center">This experiment is running right now with PID <?=$this->process_id;?></p>
            </div>
        <?php
        } ?>
            <br/>
            <div style="text-align:center">
        <?php 
        if(!$IsStarted)
        { ?>
                <button class="btn btn-primary" id="start_training"><span class="glyphicon glyphicon-play-circle"></span> Start training</button>
        <?php
        }
        else if($IsRunning)
        { ?>
                <button class="btn btn-danger" id="stop_training"><span class="glyphicon glyphicon-remove-sign"></span> Stop training</button>
        <?php 
        } ?>
        <?php
        if($IsRunning || $IsFinished)
        { ?>
                <button class="btn btn-primary" id="show_net_and_log"><span class="glyphicon glyphicon-list-alt"></span> Show net and log</button>
        <?php
        } ?>
                <a href="new.php?experiment_id=<?=$this->id;?>"class="btn btn-primary" id="fork_experiment" title="Create a new experiment based on the current one"><span class="glyphicon glyphicon-random"></span> Fork a copy</a>
        <?php
        if($IsFinished)
            { ?>
                <a href="services/download-experiment-zip.php?experiment_id=<?=$this->id;?>" target="_blank" class="btn btn-primary" id="download_output"><span class="glyphicon glyphicon-compressed"></span> Download output</a>
            <?php
            } ?>
                <button class="btn btn-danger" id="delete_experiment"><span class="glyphicon glyphicon-trash"></span> Remove experiment</button>  
            </div>
            <div class="clearfix"></div>
            <h3><?=$this->name;?></h3>
        <?php 
        if($percent > 0)
        { ?>
            <div class="progressDiv row">
                <div class="statChartHolder col-md-8">
                    <div id="d3chart">
                    </div>
                </div>
                <div class="statRightHolder col-md-4">
                    <ul>
                        <li> <h3 id="best_train_error_text"> </h3> <span>Training <small>at epoc <span id="epoc_best_train_error_text"></span></small></span></li>
                        <li> <h3 id="best_test_error_text"> </h3> <span>Test <small>at epoc <span id="epoc_best_test_error_text"></span></small></span></li>
                    </ul>
                    <div class="row" style="margin-top:25px">
                        <div class="progress-pie-chart" data-percent="">
                            <div class="ppc-progress">
                                <div class="ppc-progress-fill"></div>
                            </div>
                            <div class="ppc-percents">
                            <div class="pcc-percents-wrapper">
                                <span>%</span>
                            </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
            <div class="clearfix" style="height:80px"></div>
              

        <?php
        if($dotfile = $this->GetDotFileContents())
        {?>
            <div id="dotfile-network" style="text-align: center">
                <script type="text/javascript">
                $.ajax({
                    url: "js/viz.js",
                    dataType: "script",
                    success: function()
                    {
                        $("#dotfile-network").html(Viz('<?=str_replace("\n", " ", $dotfile);?>', { format: "png-image-element", engine: "circo" }));
                        $("#dotfile-network > img").css( "max-width", "100%" );
                        $("#dotfile-network > img").css( "height", "auto" );
                    }
                });
                </script>
            </div>
        <?php
        }
        ?>
            <div class="clearfix" style="height:40px"></div>

            <div class="row" id="index_net_and_log" style="display:none">
                <h3 style="text-align:center"><span class="glyphicon glyphicon-list-alt"></span> Current net and log</h3>
                <br/>
                <div class="col-md-6">
                    <div class="shell-wrap">
                        <p class="shell-top-bar">Current net </p>
                        <pre>
                            <code data-language="c" id="full_netcode" style="font-size:0.8em"><?=$this->RenderCurrentNet();?></code>
                        </pre>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="shell-wrap">
                        <p class="shell-top-bar">CLL : Console Layers Log</p>
                        <div class="shell-body" id="log-text">
                            <?=$this->RenderTerminalLog();?>     
                        </div>
                    </div>
                </div>
            </div>

            <div class="clearfix"></div>
            <div class="clearfix"></div>
        <?php
        }
    }


///////
}

?>