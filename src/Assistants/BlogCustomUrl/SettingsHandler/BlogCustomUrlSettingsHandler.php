<?php

namespace Blog\Assistants\BlogCustomUrl\SettingsHandler;

use Plenty\Modules\PluginMultilingualism\Contracts\PluginTranslationRepositoryContract;
use Plenty\Modules\Wizard\Contracts\WizardSettingsHandler;

/**
 * Class BlogCustomUrlSettingsHandler
 * @package Blog\Assistants\BlogCustomUrl\SettingsHandler
 */
class BlogCustomUrlSettingsHandler implements WizardSettingsHandler
{
    /**
     * @param array $parameters
     * @return mixed
     * @throws \Exception
     */
    public function handle(array $parameters)
    {
        $translationRepository = pluginApp(PluginTranslationRepositoryContract::class);
        $preparedData = [];
        $translationIdsForDeletion = [];

        $data = $parameters['data'];
        $optionId = $parameters['optionId'];

        $filters = [
            'pluginName' => 'Blog',
            'languageCode' => $data['languageCode'],
            'pluginSetId' => $data['pluginSetId']
        ];

        $existingTranslations = $translationRepository->listTranslations($filters);

        $fillables = [
            'urlName', 'entrypointMessage', 'backToStoreMessage', 'landingTitle', 'landingMetaTitle'
        ];

        $resetIfEmpty = [
            'urlName', 'entrypointMessage', 'backToStoreMessage',
        ];

        foreach($fillables as $fillable) {

            // If the key is supposed to be reset if empty -- AND it's empty. Shocking
            if(in_array($fillable, $resetIfEmpty) && empty($data[$fillable])) {
                // We can't use collections in plugins.. yet
                foreach($existingTranslations as $translation) {
                    if($translation['key'] == $fillable) {
                        $translationIdsForDeletion[] = $translation['id'];
                    }
                }

            }else{
                $preparedData[] = [
                    'pluginName' => 'Blog',
                    'fileName' => 'CustomUrl.properties',
                    'key' => $fillable,
                    'pluginSetId' => $data['pluginSetId'],
                    'languageCode' => $data['languageCode'],
                    'value' => $data[$fillable] ?? ''
                ];
            }
        }

        try{
            foreach($preparedData as $individualData) {
                $translationRepository->updateOrCreateTranslation($individualData);
            }
            foreach($translationIdsForDeletion as $translationId) {
                $translationRepository->deleteTranslation($translationId);
            }
            return true;
        }catch(\Exception $exception){
            throw $exception;
        }

    }
}