<?php

class ErrorController
{
	public function __construct()
	{

	}

	public function error($code = 404)
	{

		http_response_code($code);

		switch (true)
		{
			case ($code === 404):
			{
				return 404;
				break;
			}
			case ($code === 500):
			{
				return 500;
				break;
			}
			case ($code === 503):
			{
				return 503;
				break;
			}
		}
		//exit;
	}

	public function maintenance()
	{
		$this->error(503);
	}

	public function notFound()
	{
		$this->error(404);
	}

	public function serverError()
	{
		$this->error(500);
	}

	public function nonallowedCountry()
	{

		//exit;
	}
}
