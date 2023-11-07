<?php
if (!defined('TYPO3')) {
    die('Access denied.');
}

call_user_func(static function (): void {
    $extConf = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
        \JWeiland\Events2\Configuration\ExtConf::class
    );

    // check, if category is required
    if ($extConf->getCategoryIsRequired()) {
        $GLOBALS['TCA']['tx_events2_domain_model_event']['columns']['categories']['config']['minitems'] = 1;
    }

    // check, if organizer is required
    if ($extConf->getOrganizerIsRequired()) {
        $GLOBALS['TCA']['tx_events2_domain_model_event']['columns']['organizers']['config']['minitems'] = 1;
    }

    // check, if location is required
    if ($extConf->getLocationIsRequired()) {
        $GLOBALS['TCA']['tx_events2_domain_model_event']['columns']['location']['config']['minitems'] = 1;
    }
});
