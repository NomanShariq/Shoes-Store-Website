import {useEffect} from '@wordpress/element';

export const useLinkIcon = ({enabled, email, icon}) => {
    useEffect(() => {
        if (enabled) {
            //let el = document.querySelector('.stripe-link-icon-container');
            let el = document.getElementById('email')?.parentElement;
            if (el && !el.classList.contains('stripe-link-icon-container')) {
                removeElement('.wc-stripe-link-icon');
                el.classList.add('stripe-link-icon-container');
                const iconEl = document.createElement('template');
                iconEl.innerHTML = icon;
                el.append(iconEl.content.firstChild);
            }
        }
    }, [enabled, email]);
}

const removeElement = (className) => {
    const el = document.querySelector(className);
    if (el) {
        el.remove();
    }
}