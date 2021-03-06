<?php


// AUTOTESTER - automated compiling, execution, debugging, testing and profiling
// (c) Vedran Ljubovic and others 2014-2019.
//
//     This program is free software: you can redistribute it and/or modify
//     it under the terms of the GNU General Public License as published by
//     the Free Software Foundation, either version 3 of the License, or
//     (at your option) any later version.
// 
//     This program is distributed in the hope that it will be useful,
//     but WITHOUT ANY WARRANTY; without even the implied warranty of
//     MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
//     GNU General Public License for more details.
// 
//     You should have received a copy of the GNU General Public License
//     along with this program.  If not, see <http://www.gnu.org/licenses/>.

// Matlab.php - routines specific for Matlab programming language


class Matlab extends Language {
	// Patch for Matlab
	public function patch($options) {
		$primaryFile = $this->findPrimaryFile();
		if (!$primaryFile)
			return array( "success" => false, "message" => "Couldn't find main function" );
		
		// Create backup, return file from backup (in case of folder reuse)
		$backupFile = $primaryFile . ".patch-backup";
		if (!file_exists($backupFile)) 
			copy($primaryFile, $backupFile);
		
		// Use Octave to test for "end"
		$main_source_code = file_get_contents($primaryFile);
		global $conf_tools;
		$found_octave = false;
		foreach($conf_tools['execute'] as $tool)
			if (array_key_exists("name", $tool) && $tool['name'] == "octave")
				$found_octave = $tool;
		if ($found_octave) {
			$testFile = $primaryFile . ".test.m";
			file_put_contents($testFile, "disp(\"\")\n\n".$main_source_code."\ndisp(\"Hello, autotester!\")");
			$output = shell_exec($found_octave['path']." ".$testFile." 2>&1");
			if (!strstr($output, "Hello, autotester!") && !strstr($output, "error:"))
				$main_source_code .= "\nend\n";
		} else {
			$src = trim(preg_replace("/\%.*?$/", "", $main_source_code));
			if (!Utils::endsWith($src, "end"))
				$main_source_code .= "\nend\n";
		}
	
		file_put_contents($primaryFile, "disp(\"\")\n\n".$main_source_code."\n".$options['code']);

		return array( "success" => true, "primaryFile" => $primaryFile );
	}

}

?>
