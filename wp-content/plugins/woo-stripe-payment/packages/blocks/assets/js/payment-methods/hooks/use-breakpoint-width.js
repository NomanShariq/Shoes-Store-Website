import {useState, useEffect, useCallback} from '@wordpress/element';
import {storeInCache, getFromCache} from "../util";

export const useBreakpointWidth = (
    {
        name,
        width,
        node,
        className
    }) => {
    const [windowWidth, setWindowWidth] = useState(window.innerWidth);
    const getMaxWidth = useCallback((name) => {
        const maxWidth = getFromCache(name);
        return maxWidth ? parseInt(maxWidth) : 0;
    }, []);
    const setMaxWidth = useCallback((name, width) => storeInCache(name, width), []);

    useEffect(() => {
        const el = typeof node === 'function' ? node() : node;

        if (el) {
            const maxWidth = getMaxWidth(name);
            if (!maxWidth || width > maxWidth) {
                setMaxWidth(name, width);
            }
            if (el.clientWidth < width) {
                el.classList.add(className);
            } else {
                if (el.clientWidth > maxWidth) {
                    el.classList.remove(className);
                }
            }
        }
    }, [windowWidth, node]);
    useEffect(() => {
        const handleResize = () => setWindowWidth(window.innerWidth);
        window.addEventListener('resize', handleResize);
        return () => window.removeEventListener('resize', handleResize);
    });
}

export const useExpressBreakpointWidth = (
    {
        payment_method,
        width
    }) => {
    const node = useCallback(() => {
        const el = document.getElementById(`express-payment-method-${payment_method}`);
        return el ? el.parentNode : null;
    }, []);
    useBreakpointWidth({
        name: 'expressMaxWidth',
        width,
        node,
        className: 'wc-stripe-express__sm'
    });

}