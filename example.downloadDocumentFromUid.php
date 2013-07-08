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
$documentId = 5;

/*****
* Initialize SESSION
*****/
$KimiosPhpSoap = new KimiosPhpSoap();
$KimiosPhpSoap->connect($config['url'],$config['userSource'],
							$config['userName'],$config['password']);
$sessionId = $KimiosPhpSoap->getSessionId(); 

/*****
* get document informations
*****/
$DocumentService = new DocumentService(
	$KimiosPhpSoap->getWsdl($config['url'], 'DocumentService'), 
	$KimiosPhpSoap->getArrayOptionsService($config['url'], 'DocumentService'));

$getDocument = new getDocument(
	array('sessionId' => $sessionId,
		'documentId' => $documentId)
);

$documentResp = $DocumentService->getDocument($getDocument);
$Document = $documentResp->return;

$fileName = $Document->name.".".$Document->extension;
$mimeType = $Document->mimeType;

/*****
* Get last version to download for the retrieved document
*****/
$DocumentVersionService = new DocumentVersionService(
	$KimiosPhpSoap->getWsdl($config['url'], 'DocumentVersionService'), 
	$KimiosPhpSoap->getArrayOptionsService($config['url'], 'DocumentVersionService')
);

$lastDocumentVersion = new getLastDocumentVersion(
	array('sessionId' => $sessionId,
		'documentId' => $documentId)
);
$lastDvResp = $DocumentVersionService->getLastDocumentVersion($lastDocumentVersion);
$dvUid = $lastDvResp->return->uid;

/*****
* declare a download start on the server
*****/
$FileTransferService = new FileTransferService(
	$KimiosPhpSoap->getWsdl($config['url'],'FileTransferService'), 
	$KimiosPhpSoap->getArrayOptionsService($config['url'],'FileTransferService'));

$downloadTransaction = new startDownloadTransaction(
	array('sessionId' 		=> $sessionId,
		'documentVersionId' => $dvUid,
		'isCompressed' 		=> false)
);

$stDlResp = $FileTransferService->startDownloadTransaction($downloadTransaction);

$dTxUid 	= $stDlResp->return->uid;	//id of downloadTransaction
$fileSize 	= $stDlResp->return->size;	//size of document to get

// create chunk for downloading file
$chunkSize 	= 1024;
$offset 	= 0;
$chk = new getChunck(
	array('transactionId' 	=> $dTxUid,
		'sessionId' 		=> $sessionId,
		'chunkSize' 		=> $chunkSize,
		'offset' 			=> $offset)
);

header("Content-Type: ".$mimeType."; name=\"".$fileName."\"");
header("Content-Transfer-Encoding: binary");
header("Content-Length: ".$fileSize."");
header("Content-Disposition: attachment; filename=\"".$fileName."\"");
header("Expires: 0");
header("Cache-Control: no-cache, must-revalidate");
header("Pragma: no-cache");

while($offset < $fileSize){
	if(($offset + $chunkSize)>$fileSize){
		$chunkSize = ($fileSize - $offset);
	}
	$chk->offset 	= $offset;
	$chk->chunkSize = $chunkSize;
	$chkResp 		= $FileTransferService->getChunck($chk);	
	file_put_contents('php://output', $chkResp->return);
	$offset 		= $offset + $chunkSize;
}
