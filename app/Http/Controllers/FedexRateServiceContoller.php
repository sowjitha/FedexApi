<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Http\JsonResponse;
use App\Helpers\FedexHelper;

define('RATEWSDL', resource_path('wsdl/RateService_v22.wsdl'));

class FedexRateServiceContoller extends Controller
{
    private function getVersion ()
    {
        return array( "Version" => array(
            'ServiceId' => 'crs',
            'Major' => 22,
            'Intermediate' => 0,
            'Minor' => 0
        ));
    }


    private function buildRequest($input){
        $RateServiceRequest['WebAuthenticationDetail'] = FedexHelper::getWebAuthenticationDetail()['ucred'];
        $RateServiceRequest['ClientDetail'] = FedexHelper::getClientDetail()['ClientDetail'];
        $RateServiceRequest['TransactionDetail'] = FedexHelper::getTransactionDetail()['TransactionDetail'];
        $RateServiceRequest['Version'] = $this->getVersion()['Version'];
        $RateServiceRequest['RequestedShipment']['DropoffType'] = 'REGULAR_PICKUP';
        $RateServiceRequest['RequestedShipment']['ShipTimestamp'] = date('c');
        $RateServiceRequest['RequestedShipment']['ServiceType'] = 'INTERNATIONAL_PRIORITY';
        $RateServiceRequest['RequestedShipment']['PackagingType'] = 'YOUR_PACKAGING';
        $RateServiceRequest['RequestedShipment']['TotalInsuredValue'] = FedexHelper::totalInsuredValue();
        $RateServiceRequest['RequestedShipment']['Shipper'] = FedexHelper::getProperty('shipper');
        $RateServiceRequest['RequestedShipment']['Recipient'] = FedexHelper::getProperty('recipient');
        $RateServiceRequest['RequestedShipment']['ShippingChargesPayment'] = FedexHelper::getProperty('shippingchargespayment');
        $RateServiceRequest['RequestedShipment']['PackageCount'] = '1';
        $RateServiceRequest['RequestedShipment']['RequestedPackageLineItems'] = FedexHelper::addPackageLineItem1();
        return $RateServiceRequest;

    }


    public function rateService(Request $request){


        $validateClient = FedexHelper::getSoapClient(RATEWSDL);
        $FinalRequest  = $this->buildRequest($request);
        //print_r($FinalRequest);
        //dd("echo");
       // dd($validateClient->__getFunctions());
        try {


            $postalResponse = $validateClient -> getRates($FinalRequest);
            //dd($postalResponse);


            if ($postalResponse -> HighestSeverity != 'FAILURE' && $postalResponse -> HighestSeverity != 'ERROR'){

                FedexHelper::printSuccess($validateClient, $postalResponse);
                return new JsonResponse(["status"=>200, "data" =>$postalResponse],Response::HTTP_OK);

            }else{

                FedexHelper::printError($validateClient, $postalResponse);
                return new JsonResponse(["status"=>500,"data" =>$postalResponse],Response::HTTP_INTERNAL_SERVER_ERROR);

            }

        } catch (\SoapFault $exception) {
            dd($exception);

            FedexHelper::printFault($exception, $validateClient);
            return new JsonResponse(["status"=>500,"data" =>$exception],Response::HTTP_INTERNAL_SERVER_ERROR);

        }


    }
}
