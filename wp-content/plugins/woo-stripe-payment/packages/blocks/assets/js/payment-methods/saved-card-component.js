import {useEffect, useCallback} from '@wordpress/element';
import {handleCardAction} from '@paymentplugins/stripe/util';
import {useProcessCheckoutError} from './hooks';

const SavedCardComponent = (
    {
        eventRegistration,
        emitResponse,
        getData,
        method = 'handleCardAction'
    }) => {
    const {onCheckoutAfterProcessingWithSuccess, onCheckoutAfterProcessingWithError} = eventRegistration;
    const {responseTypes} = emitResponse;
    useProcessCheckoutError({
        responseTypes,
        subscriber: onCheckoutAfterProcessingWithError,
        messageContext: emitResponse.noticeContexts.PAYMENTS
    })
    const handleSuccessResult = useCallback(async ({redirectUrl}) => {
        return await handleCardAction({redirectUrl, getData, responseTypes, method});
    }, []);

    useEffect(() => {
        const unsubscribe = onCheckoutAfterProcessingWithSuccess(handleSuccessResult);
        return () => unsubscribe();
    }, [onCheckoutAfterProcessingWithSuccess, handleSuccessResult]);
    return null;
}

export default SavedCardComponent;
