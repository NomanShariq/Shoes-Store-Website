import {useEffect} from '@wordpress/element';

export const useProcessCheckoutError = (
    {
        responseTypes,
        subscriber,
        messageContext = null
    }) => {
    useEffect(() => {
        const unsubscribe = subscriber((data) => {
            if (data?.processingResponse.paymentDetails?.stripeErrorMessage) {
                console.log(data.processingResponse.paymentDetails.stripeErrorMessage);
                return {
                    type: responseTypes.ERROR,
                    message: data.processingResponse.paymentDetails.stripeErrorMessage,
                    messageContext
                };
            }
            return null;
        });
        return () => unsubscribe();
    }, [responseTypes, subscriber]);
}