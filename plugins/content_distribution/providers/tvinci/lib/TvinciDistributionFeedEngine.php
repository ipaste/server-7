<?php
/**
 * @package plugins.tvinciDistribution
 * @subpackage lib
 */
class TvinciDistributionFeedEngine extends DistributionEngine implements
	IDistributionEngineUpdate,
	IDistributionEngineSubmit,
	IDistributionEngineReport,
	IDistributionEngineDelete
{

	const SOAP_ENVELOPE_URL = 'http://www.w3.org/2003/05/soap-envelope';

	/* (non-PHPdoc)
	 * @see IDistributionEngineSubmit::submit()
	 */
	public function submit(KalturaDistributionSubmitJobData $data)
	{
		return $this->handleAction($data, $data->distributionProfile, $data->providerData, "Submit");
	}

	/* (non-PHPdoc)
	 * @see IDistributionEngineUpdate::update()
	 */
	public function update(KalturaDistributionUpdateJobData $data)
	{
		return $this->handleAction($data, $data->distributionProfile, $data->providerData, "Update");
	}

	/* (non-PHPdoc)
	 * @see IDistributionEngineDelete::delete()
	*/
	public function delete(KalturaDistributionDeleteJobData $data)
	{
		return true;
	}

	private function validateProviderDataAndDistributionProfile(KalturaDistributionJobData $data){
		if (!$data->distributionProfile || !($data->distributionProfile instanceof KalturaTvinciDistributionProfile))
			throw new Exception("Distribution profile must be of type KalturaTvinciDistributionProfile");
		if (!$data->providerData || !($data->providerData instanceof KalturaTvinciDistributionJobProviderData))
			throw new Exception("Provider data must be of type KalturaTvinciDistributionJobProviderData");
	}


	/* (non-PHPdoc)
	 * @see IDistributionEngineReport::fetchReport()
	 */
	public function fetchReport(KalturaDistributionFetchReportJobData $data)
	{
		return false;
	}

	/**
	 * @param KalturaDistributionJobData $data
	 * @param KalturaTvinciDistributionProfile $distributionProfile
	 * @param KalturaTvinciDistributionJobProviderData $providerData
	 * @param $actionType
	 * @return bool
	 * @throws Exception
	 */
	private function handleAction(KalturaDistributionJobData $data, KalturaTvinciDistributionProfile $distributionProfile,
								  KalturaTvinciDistributionJobProviderData $providerData, $actionType){
		$this->validateProviderDataAndDistributionProfile($data);
		$url = $distributionProfile->ingestUrl;
		KalturaLog::info("Tvinci Distribution action {$actionType}".
						 ",entry {$data->entryDistribution->entryId}, url: {$url}\nXML data:\n{$providerData->xml}");

		$result = $this->postXml($url, $providerData->xml);
		$success = ($result->status == 'OK' && $result->tvmID != '');
		if (!$success) {
			KalturaLog::err("Tvinci distribution action {$actionType} has failed with description: {$result->description} ".
							"and status: {$result->status}");
			throw new Exception("{$actionType} failed - reason {$result->description}");
		}
		return true;
	}

	/**
	 * @param string $url
	 * @param string $xml
	 * @throws Exception in case of failure to receive a response
	 * @return SimpleXMLElement
	 */
	protected function postXml($url, $xml)
	{
		$response = self::curlPost($url, $xml);
		KalturaLog::info("Post XML Full response: " . print_r($response,true));

		$retrunObject = null;
		if ( $response['http_code'] == KCurlHeaderResponse::HTTP_STATUS_OK )
		{
			try
			{
				/**
				 * this is an exemplary response from the OTT servers per legal request
				 * <s:Envelope xmlns:s="http://www.w3.org/2003/05/soap-envelope" xmlns:a="http://www.w3.org/2005/08/addressing">
				 * 	<s:Header>
				 * 		<a:Action s:mustUnderstand="1">urn:Iservice/InjestTvinciDataResponse</a:Action>
				 * </s:Header>
				 * <s:Body>
				 * 		<InjestTvinciDataResponse>
				 * 			<InjestTvinciDataResult xmlns:i="http://www.w3.org/2001/XMLSchema-instance">
				 * 				<status>OK</status>
				 * 				<description/>
				 * 				<assetID>1234Wicked</assetID>
				 * 				<tvmID>279473</tvmID>
				 * 			</InjestTvinciDataResult>
				 * 		</InjestTvinciDataResponse>
				 * 	</s:Body>
				 * </s:Envelope>
				 */
				$responseXml = simplexml_load_string($response['content']);
				$childs = $responseXml->children(self::SOAP_ENVELOPE_URL)->Body;
				$bodyElement = $childs->xpath('//s:Body');
				$returnObject = $bodyElement[0]->InjestTvinciDataResponse->InjestTvinciDataResult;
			}
			catch (Exception $e)
			{

				throw new Exception("Failed parsing response due to {$e->getMessage()}"); // Throw an Exception in order to fail the job
			}
		} else {
			throw new Exception("Failed communication to host - response code is: {$response['http_code']}".
													", error description is: {$response['error_text']}");
		}

		return $returnObject;
	}
	

	public static function curlPost($url, $postData)
	{
		$ch = curl_init();

		// set URL and other appropriate options
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_HEADER, false);
		curl_setopt($ch, CURLOPT_NOBODY, false);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
	
		curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/soap+xml', 'charset: utf-8'));
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);

		$content = curl_exec($ch);
		$curlError = curl_error($ch);
		$curlHttpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

		curl_close($ch);
	
		$response = array('content' => $content, 'http_code' => $curlHttpCode, 'error_text' => $curlError);

		return $response;
	}
}