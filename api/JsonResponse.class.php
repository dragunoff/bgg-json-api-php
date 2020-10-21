<?php

namespace DaIgraem\BggProxyApi;

class JsonResponse
{
	private $success = false;
	private $data = [];

	function setData($data) : self
	{
		$this->data = $data;
		return $this;
	}

	function getData()
	{
		return $this->data;
	}

	function setErrorMessage($message) : self
	{
		$this->data = ['error' => $message];
		return $this;
	}

	function setSuccess(bool $success) : self
	{
		$this->success = (bool) $success;
		return $this;
	}

	function send(): void
	{
		$response = [
			'success' => $this->success,
			'data' => $this->data,
		];

		echo json_encode($response);
		exit();
	}
}
