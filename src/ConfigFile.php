<?php

class ConfigFile
{
	private array $lines  = [];
	private array $config = [];

	public function __construct(string $raw)
	{
		$this->lines = explode("\n", $raw);

		foreach ($this->lines as &$line)
			$line = trim($line);

		$this->parse();
	}

	public function __invoke(string $section, string $config):?string
	{
		return $this->config[$section][$config] ?? null;
	}

	public function int(string $section, string $config):?int
	{
		if (!isset($this->config[$section][$config]))
			return null;

		if (!is_numeric($this->config[$section][$config]))
			throw new Exception("Field can not cast into integer.");

		return intval($this->config[$section][$config]);
	}

	public function float(string $section, string $config):?float
	{
		if (!isset($this->config[$section][$config]))
			return null;

		if (!is_numeric($this->config[$section][$config]))
			throw new Exception("Field can not cast into float.");

		return floatval($this->config[$section][$config]);
	}

	public function bool(string $section, string $config):?bool
	{
		if (!isset($this->config[$section][$config]))
			return null;

		$field = $this->config[$section][$config];

		if (!strcasecmp($field, 'true'))
			return true;
		elseif (!strcasecmp($field, 'false'))
			return false;
		else
			throw new Exception("Field can not cast into boolean.");
	}

	public function __toString():string {
		$lines = ["config"];

		$n_sections = count($this->config);
		$c_sections = 0;

		foreach ($this->config as $section => $config) {
			$c_sections++;
			$last_section = $c_sections == $n_sections;

			if ($last_section)
				$lines[] = "└── {$section}";
			else
				$lines[] = "├── {$section}";

			$n_configs = count($config);
			$c_configs = 0;

			foreach ($config as $key => $value) {
				$c_configs++;
				$last_config = $c_configs == $n_configs;

				if ($last_section && $last_config)
					$lines[] = "    └── {$key}: {$value}";

				if ($last_section && !$last_config)
					$lines[] = "    ├── {$key}: {$value}";

				if (!$last_section && $last_config)
					$lines[] = "│   └── {$key}: {$value}";

				if (!$last_section && !$last_config)
					$lines[] = "│   ├── {$key}: {$value}";
			}
		}

		return implode(PHP_EOL, $lines);
	}

	private function parse():void
	{
		$last_section = null;

		foreach ($this->lines as $i => $line) {
			if (!$line) // empty line
				continue;
			elseif (in_array($line[0], ['#', ';', '!'], true)) // commented
				continue;
			elseif ($line[0] === '[') { // section
				if ($line[strlen($line) - 1] !== ']')
					throw new Exception("Invalid section {$line} on line {$i}: not ending with ']'.");

				$section = trim(substr($line, 1, strlen($line) - 2));

				if (!$section)
					throw new Exception("Invalid section {$line} on line {$i}: empty section name.");

				if (array_key_exists($section, $this->config))
					throw new Exception("Invalid section {$line} on line {$i}: duplicated section {$section}.");

				$this->config[$section] = [];
				$last_section           = $section;
			}
			else { // config
				if ($last_section === null)
					throw new Exception("Invalid config {$line} on line {$i}: not contained by any section.");

				if (strpos($line, '=') === false)
					throw new Exception("Invalid config {$line} on line {$i}: no equal sign interpreted.");

				$_line = explode('=', $line, 2);

				$key = trim($_line[0]);
				$value = trim($_line[1]);

				if (!$key)
					throw new Exception("Invalid config {$line} on line {$i}: empty config key.");

				if (array_key_exists($key, $this->config[$last_section]))
					throw new Exception("Invalid config {$line} on line {$i}: duplicated key {$key} in section {$last_section}.");

				$this->config[$last_section][$key] = $value;
			}
		}
	}
}

?>
