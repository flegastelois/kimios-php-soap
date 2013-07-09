<?php
/*
 * Kimios - Document Management System Software
 * Copyright (C) 2012-2013  DevLib'
 * Copyright (C) 2013 - François Legastelois (flegastelois@teclib.com)
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 2 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

//require init api file
require('init.api.php');

//Specific configs for this example
$path = "/WorkspaceExample/DossierExample/example_uploadDocument.odt";
$localDocument = "./example_uploadDocument.odt";

/*****
* Initialize SESSION
*****/
$KimiosPhpSoap = new KimiosPhpSoap();
$KimiosPhpSoap->connect($config['url'],$config['userSource'],
							$config['userName'],$config['password']);
$sessionId = $KimiosPhpSoap->getSessionId(); 

// Initialize services we want to use
$SearchService = new SearchService(
	$KimiosPhpSoap->getWsdl($config['url'], 'SearchService'), 
	$KimiosPhpSoap->getArrayOptionsService($config['url'], 'SearchService'));

$DocumentService = new DocumentService(
	$KimiosPhpSoap->getWsdl($config['url'], 'DocumentService'), 
	$KimiosPhpSoap->getArrayOptionsService($config['url'], 'DocumentService'));

$DocumentVersionService = new DocumentVersionService(
	$KimiosPhpSoap->getWsdl($config['url'], 'DocumentVersionService'), 
	$KimiosPhpSoap->getArrayOptionsService($config['url'], 'DocumentVersionService'));

// Search if document is already in kimios
$getDMentityFromPath = new getDMentityFromPath(
	array('sessionId' => $sessionId,
		'path' => $path)
);

try{
	$SearchService->getDMentityFromPath($getDMentityFromPath);
	$documentExists = true;
} catch(SoapFault $fault) {
	$documentExists = false;
}

if($documentExists) {
	// if document already exists, just create new version an upload
	$documentSearchResp = $SearchService->getDMentityFromPath($getDMentityFromPath);
	$documentId = $documentSearchResp->return->uid;

	$createDocumentVersion = new createDocumentVersion(
		array(	'sessionId'  => $sessionId,
				'documentId' => $documentId)
	);

	$DocumentVersionService->createDocumentVersion($createDocumentVersion);
} else {
	// if document doesn't exists, just create it
	$createDocumentFromFullPath = new createDocumentFromFullPath(
		array(	'sessionId' 			=> $sessionId,
				'path' 					=> $path,
				'isSecurityInherited' 	=> true)
	);

	$createDocFFPathResp = $DocumentService->createDocumentFromFullPath($createDocumentFromFullPath);
	$documentId = $createDocFFPathResp->return;
}

/*****
* declare a upload start on the server
*****/
$FileTransferService = new FileTransferService(
	$KimiosPhpSoap->getWsdl($config['url'],'FileTransferService'), 
	$KimiosPhpSoap->getArrayOptionsService($config['url'],'FileTransferService'));

$uploadTransaction = new startUploadTransaction(
	array('sessionId' 	=> $sessionId,
		'documentId' 	=> $documentId,
		'isCompressed' 	=> false)
);

$startUploadTransactionResp = $FileTransferService->startUploadTransaction($uploadTransaction);
$transactionId = $startUploadTransactionResp->return->uid;

//filesize in local
$localDocument_filesize = filesize($localDocument);
$localDocument_md5		= md5_file($localDocument);
$localDocument_sha1		= sha1_file($localDocument);

//get local content of document
$localDocument_handle 	= fopen($localDocument, "r");
$localDocument_content 	= fread($localDocument_handle, $localDocument_filesize);
fclose($localDocument_handle);

// TODO, upload block by block
$sendChunk = new sendChunk(
	array(	'sessionId' 	=> $sessionId,
			'transactionId' => $transactionId,
			'data' 			=> $localDocument_content)
);
$FileTransferService->sendChunk($sendChunk);

$endUploadTransaction = new endUploadTransaction(
	array('sessionId' 	=> $sessionId,
		'transactionId' => $transactionId,
		'md5' 			=> $localDocument_md5,
		'sha1' 			=> $localDocument_sha1)
);

$endUploadTransactionResp = $FileTransferService->endUploadTransaction($endUploadTransaction);

echo "Upload ok :) check Kimios ;)";