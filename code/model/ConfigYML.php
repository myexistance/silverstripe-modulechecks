<?php

//require ('../modulechecks/vendor/autoload.php');

use Symfony\Component\Yaml\Yaml;


Class ConfigYML extends Object {

    public function ConfigYML($gitHubModuleInstance) {
        if (! $gitHubModuleInstance) {
            user_error ("ConfigYML needs an instance of GitHubModule");
        }
        $this->gitHubModuleInstance = $gitHubModuleInstance;
        $this->moduleName = $gitHubModuleInstance->ModuleName;
        $this->yaml_data = null;
        $folder = GitHubModule::Config()->get('absolute_temp_folder');

        $this->filename = $folder . '/' . $this->moduleName . '/_config/config.yml';
    }

    public function reWrite(){
		if (! $this->readYMLFromFile())
		{
			return false;
		}
		if (! $this->writeYAMLToFile())
		{
			return false;
		}
		return true;
	}

    public function readYMLFromFile() {

		GeneralMethods::output_to_screen("reading config yml ...  ",'updating');

		if (! file_exists ($this->filename))
		{
                    GeneralMethods::output_to_screen("<li>Unable to load: " . $this->filename, 'updated') ;
                    //UpdateModules::$unsolvedItems[$this->gitHubModuleInstance->ModuleName] = "Unable to load " . $this->filename;

                    UpdateModules::addUnsolvedProblem($this->gitHubModuleInstance->ModuleName, "Unable to load " . $this->filename);
                    return false;
		}

        try {
            $this->yaml_data = Yaml::parse(file_get_contents($this->filename));




        } catch (Exception $e) {
            GeneralMethods::output_to_screen("<li>Unable to parse the YAML string: " .$e->getMessage(). " <li>", 'updated') ;

            //UpdateModules::$unsolvedItems[$this->gitHubModuleInstance->ModuleName] = "Unable to parse the YAML string: " .$e->getMessage();

            UpdateModules::addUnsolvedProblem($this->gitHubModuleInstance->ModuleName, "Unable to parse the YAML string: " .$e->getMessage());

			//trigger_error ("Error in YML file");

			$this->replaceFaultyYML();

			return false;
        }


        return $this->yaml_data;

    }


    public function replaceFaultyYML() {

        return false;

		/**function broken do not use**/


		if (file_exists ($this->filename)) {


			$rawYML = file_get_contents($this->filename);

			$lines = explode("\n", $rawYML);

			$replacment = '';

			foreach ($lines as $index=>$line) {
				if (strpos ($line, "After:" ) !==false) {
					$replacment = "After:";
					$listitems = explode (',', $line);
					//print_r ($listitems);
					foreach ($listitems as $item)
					{
						if (! trim ($item)) {
							continue;
						}

						$item = str_replace('After: ', '', $item);
						$replacment .= '  - '. trim($item) . "";
					}
					$lines[$index] = $replacment;
				}
			}
			$newYML = implode('', $lines);

			GeneralMethods::output_to_screen("Updating config.YML to correct syntax ... ",'updating');

			$file = fopen($this->filename, "w");



			file_put_contents($this->filename, $newYML);


		}
		else
		{
			return false;
		}

    }
    public function writeYAMLToFile() {

		GeneralMethods::output_to_screen("Writing config yml ... ",'updating');

		if (!$this->yaml_data) {
			return false;
		}

		$yaml = Yaml::dump($this->yaml_data);
		file_put_contents($this->filename, $yaml);
		return true;


	}

    private function catchFopenWarning() {

    }

}

