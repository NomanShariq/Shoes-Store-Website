import {Component} from '@wordpress/element';

class ErrorBoundary extends Component {
    constructor(props) {
        super(props);
        this.state = {hasError: false, error: null, errorInfo: null};
    }

    componentDidCatch(error, errorInfo) {
        this.setState({
            hasError: true,
            error,
            errorInfo
        })
    }

    render() {
        if (this.state.hasError) {
            return (
                <>
                    {this.state.error && <div className='wc-stripe-block-error'>{this.state.error.toString()}</div>}
                    {this.state.errorInfo &&
                    <div className='wc-stripe-block-error'>{this.state.errorInfo.componentStack}</div>}
                </>
            )
        }
        return this.props.children;
    }
}

export default ErrorBoundary;