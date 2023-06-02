import {useEffect, useRef, useState} from '@wordpress/element';
import {ensureErrorResponse} from "../../util";
import {__} from "@wordpress/i18n";

export const useValidateCheckout = (
    {
        subscriber,
        responseTypes,
        component = null,
        msg = __('Please enter your payment info before proceeding.', 'woo-stripe-payment')
    }) => {
    const [isValid, setIsValid] = useState(false);

    useEffect(() => {
        const unsubscribe = subscriber(() => {
            if (component && !isValid) {
                return ensureErrorResponse(responseTypes, msg);
            }
            return true;
        });
        return () => unsubscribe();
    }, [
        subscriber,
        isValid,
        setIsValid,
        responseTypes,
        component
    ]);
    return {isValid, setIsValid};
}