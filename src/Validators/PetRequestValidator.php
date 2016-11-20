<?php

namespace WellCat\Validators;

use DateTime;
use Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use WellCat\JsonResponse;

class PetRequestValidator
{
    protected $app;

    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    public function ValidatePetCreationRequest(Request $request)
    {
        $success = true;
        $error = null;
        $parameters = null;

        // Get parameters
        $name = $request->request->get('name');
        $animalTypeID = $request->request->get('animalTypeID');
        $breed = $request->request->get('breed');
        $gender = $request->request->get('gender');
        $dateOfBirth = $request->request->get('dateOfBirth');
        $weight = $request->request->get('weight');
        $height = $request->request->get('height');
        $length = $request->request->get('length');

        // Validate parameters
        if (!$name) {
            $success = false;
            $error = JsonResponse::missingParam('name');
        }
        elseif (!$animalTypeID) {
            $success = false;
            $error = JsonResponse::missingParam('animalTypeID');
        }
        elseif (!$breed) {
            $success = false;
            $error = JsonResponse::missingParam('breed');
        }
        elseif (!$gender) {
            $success = false;
            $error = JsonResponse::missingParam('gender');
        }
        elseif (!$dateOfBirth) {
            $success = false;
            $error = JsonResponse::missingParam('dateOfBirth');
        }
        elseif (!$weight) {
            $success = false;
            $error = JsonResponse::missingParam('weight');
        }
        elseif (!$height) {
            $success = false;
            $error = JsonResponse::missingParam('height');
        }
        elseif (!$length) {
            $success = false;
            $error = JsonResponse::missingParam('length');
        }
        elseif (!DateTime::createFromFormat('Y-m-d', $dateOfBirth)) {
            $success = false;
            $error = JsonResponse::userError('Invalid date.');
        }
        elseif (!$this->app['api.animalservice']->CheckBreedExists($breed)) {
            $success = false;
            $error = JsonResponse::userError('Invalid breed.');
        }
        else {
            $parameters = Array(
                'name' => $name,
                'animalTypeID' => $animalTypeID,
                'breed' => $breed,
                'gender' => $gender,
                'dateOfBirth' => $dateOfBirth,
                'weight' => $weight,
                'height' => $height,
                'length' => $length
            );
        }

        // Validate animal specific parameters if necessary
        if ((int)$animalTypeID == 1) {
            $catValidationResult = $this->ValidatePetCatCreationRequest($request);

            if (!$catValidationResult->GetSuccess()) {
                $success = $catValidationResult->GetSuccess();
                $error = $catValidationResult->GetError();
            }
            else {
                $parameters = array_merge($parameters, $catValidationResult->GetParameters());
            }
        }

        return new RequestValidationResult($success, $parameters, $error);
    }

    private function ValidatePetCatCreationRequest(Request $request)
    {
        $success = true;
        $error = null;
        $parameters = null;

        // Get parameters
        $declawed = $request->request->get('declawed');
        $outdoor = $request->request->get('outdoor');
        $fixed = $request->request->get('fixed');

        // Validate parameters
        if (!$declawed) {
            $success = false;
            $error = JsonResponse::missingParam('declawed');
        }
        elseif (!$outdoor) {
            $success = false;
            $error = JsonResponse::missingParam('outdoor');
        }
        elseif (!$fixed) {
            $success = false;
            $error = JsonResponse::missingParam('fixed');
        }
        else {
            $parameters = Array(
                'declawed' => $declawed,
                'outdoor' => $outdoor,
                'fixed' => $fixed
            );
        }

        return new RequestValidationResult($success, $parameters, $error);
    }

    public function ValidateSetPetAccessibilityRequest(Request $request)
    {
        $success = true;
        $error = null;
        $parameters = null;

        // Get parameters
        $email = $request->request->get('email');
        $petID = $request->request->get('petID');
        $access = $request->request->get('access');

        // Validate parameters
        if (!$email) {
            $success = false;
            $error = JsonResponse::missingParam('email');
        }
        elseif (!$petID) {
            $success = false;
            $error = JsonResponse::missingParam('petID');
        }
        elseif (!$access) {
            $success = false;
            $error = JsonResponse::missingParam('access');
        }
        elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $success = false;
            $error = JsonResponse::userError('Invalid email');
        }
        elseif (!$this->app['api.dbtypes']->IsValidPetAccessibilityValue($access)) {
            $success = false;
            $error = JsonResponse::userError('Invalid accessibility value');
        }
        else {
            $parameters = Array(
                'petID' => $petID,
                'email' => $email,
                'access' => $access
            );
        }

        return new RequestValidationResult($success, $parameters, $error);
    }

    public function ValidateUpdatePetRequest(Request $request)
    {
        $parameters = Array();
        
        // Get parameters
        $petID = $request->request->get('petID');
        $name = $request->request->get('name');
        $breed = $request->request->get('breed');
        $gender = $request->request->get('gender');
        $dateOfBirth = $request->request->get('dateOfBirth');
        $weight = $request->request->get('weight');
        $height = $request->request->get('height');
        $length = $request->request->get('length');
        
        // Ensure we have a petID
        if (!$petID) {
            return new RequestValidationResult(false, null, JsonResponse::missingParam('petID'));
        }
        elseif (!is_int($petID)) {
            return new RequestValidationResult(false, null, JsonResponse::userError('Invalid petID'));
        }
        else {
            $parameters['petID'] = $petID;
        }

        // Validate parameters (we only need at least one valid parameter for this request)
        if (isset($name)) {
            if (is_string($name) && !empty($name)) {
                $parameters['name'] = $name;
            }
            else {
                return new RequestValidationResult(false, null, JsonResponse::userError('Invalid name'));
            }
        }

        if (isset($breed)) {
            if (is_int($breed)) {
                $parameters['breed'] = $breed;
            }
            else {
                return new RequestValidationResult(false, null, JsonResponse::userError('Invalid breed'));
            }
        }

        if (isset($gender)) {
            if (is_int($gender)) {
                $parameters['gender'] = $gender;
            }
            else {
                return new RequestValidationResult(false, null, JsonResponse::userError('Invalid gender'));
            }
        }

        if (isset($dateOfBirth)) {
            if (DateTime::createFromFormat('Y-m-d', $dateOfBirth)) {
                $parameters['dateOfBirth'] = $dateOfBirth;
            }
            else {
                return new RequestValidationResult(false, null, JsonResponse::userError('Invalid date of birth'));
            }
        }

        if (isset($weight)) {
            if (is_real($weight) || is_int($weight)) {
                $parameters['weight'] = $weight;
            }
            else {
                return new RequestValidationResult(false, null, JsonResponse::userError('Invalid weight'));
            }
        }

        if (isset($height)) {
            if (is_real($height) || is_int($height)) {
                $parameters['height'] = $height;
            }
            else {
                return new RequestValidationResult(false, null, JsonResponse::userError('Invalid height'));
            }
        }

        if (isset($length)) {
            if (is_real($length) || is_int($length)) {
                $parameters['length'] = $length;
            }
            else {
                return new RequestValidationResult(false, null, JsonResponse::userError('Invalid length'));
            }
        }

        if (count($parameters) > 0) {
            return new RequestValidationResult(true, $parameters);
        }
        else {
            return new RequestValidationResult(false, null, JsonResponse::userError('At least one parameter must be set'));   
        }        
    }
}
