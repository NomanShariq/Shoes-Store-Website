import {__, sprintf} from "@wordpress/i18n";
import {getSetting} from '@woocommerce/settings'

const data = getSetting('stripeGeneralData');

export const OffsiteNotice = (
    {
        paymentText,
        buttonText = __('', 'woo-stripe-payment')
    }
) => {
    return (
        <div className="wc-stripe-blocks-offsite-notice">
            <div>
                <img src={`${data.assetsUrl}/img/offsite.svg`}/>
                <p>{sprintf(__('After clicking "%1$s", you will be redirected to %2$s to complete your purchase securely.', 'woo-stripe-payment'), buttonText, paymentText)}</p>
            </div>
        </div>
    )
}