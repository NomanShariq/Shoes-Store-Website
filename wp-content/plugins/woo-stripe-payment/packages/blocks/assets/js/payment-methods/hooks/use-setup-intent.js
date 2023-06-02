import {useEffect, useState, useCallback} from '@wordpress/element';
import apiFetch from "@wordpress/api-fetch";
import {
    getSettings,
    getRoute,
    cartContainsPreOrder,
    cartContainsSubscription,
    getFromCache,
    storeInCache,
    deleteFromCache
} from '../util';

export const useSetupIntent = (
    {
        cartTotal,
        setError
    }) => {
    const [setupIntent, setSetupIntent] = useState(getFromCache('setupIntent'));

    useEffect(() => {
        const createSetupIntent = async () => {
            if (setupIntent) {
                return;
            }
            // only create intent under certain conditions
            let result = await apiFetch({
                url: getRoute('create/setup_intent'),
                method: 'POST'
            });
            if (result.code) {
                setError(result.message);
            } else {
                storeInCache('setupIntent', result.intent);
                setSetupIntent(result.intent);
            }
        }
        if (cartContainsPreOrder() || (cartContainsSubscription() && cartTotal.value == 0)) {
            if (!setupIntent) {
                createSetupIntent();
            }
        } else {
            setSetupIntent(null);
        }
    }, [cartTotal.value]);
    const removeSetupIntent = useCallback(() => {
        deleteFromCache('setupIntent');
    }, [cartTotal.value]);
    return {setupIntent, removeSetupIntent};
}