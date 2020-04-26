/**
 * This is the configuration file for the event-engine-cockpit.
 *
 * Through altering this file you can add some custom behavior and default configuration. Since this file is
 * included during run-time, rebuilds will not be necessary.
 */

(function(exports) {

    /* Place any methods you might need below to keep the global scope clean */

    /**
     * Sends a OAuth2 client_credentials grant request to the specified server
     *
     * @param authServer
     * @param clientId
     * @param clientSecret
     * @returns {Promise<*>}
     */
    async function authenticateOAuth2ClientCredentials(authServer, clientId, clientSecret) {
        const staticData = authenticateOAuth2ClientCredentials;

        const in10Seconds = new Date((new Date()).getTime() + 10000);
        if (staticData.token && in10Seconds < staticData.validUntil) {
            return staticData.token;
        }

        const response = await fetch(authServer, {
            method: 'POST',
            mode: 'cors',
            cache: 'no-cache',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded'
            },
            body: `grant_type=client_credentials&client_id=${clientId}&client_secret=${clientSecret}`
        });

        const responseData = await response.json();
        staticData.token = responseData.access_token;
        staticData.validUntil = new Date((new Date()).getTime() + responseData.expires_in * 1000);

        return responseData.access_token;
    }

    /**
     * Sends a OAuth2 password grant request to the specified server
     *
     * @param authServer
     * @param clientId
     * @param username
     * @param password
     * @returns {Promise<*>}
     */
    async function authenticateOAuth2Password(authServer, clientId, username, password) {
        const staticData = authenticateOAuth2Password;

        const in10Seconds = new Date((new Date()).getTime() + 10000);
        if (staticData.token && in10Seconds < staticData.validUntil) {
            return staticData.token;
        }

        const response = await fetch(authServer, {
            method: 'POST',
            mode: 'cors',
            cache: 'no-cache',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded'
            },
            body: `grant_type=password&client_id=${clientId}&username=${username}&password=${password}`
        });

        const responseData = await response.json();
        staticData.token = responseData.access_token;
        staticData.validUntil = new Date((new Date()).getTime() + responseData.expires_in * 1000);

        return responseData.access_token;
    }

    /**
     * Default configuration for the event-engine-cockpit. Some of the properties defined below can also be overridden in the
     * settings dialog for individual per-browser configuration. Additionally the entire environment can be overridden
     * through the usage of certain hooks.
     */
    exports.eeUiConfig = {
        env: {
            schemaUrl: 'https://localhost/cockpit',
            messageBoxUrl: 'https://localhost/api/messagebox',
            aggregateList: {
                filterLimit: 500,
                batchSize: 30,
            },
            aggregateConfig: {
            },
            context: {
            },
        },

        /* Adjust the hooks below to alter the behavior of the event-engine-cockpit */
        hooks: {
            /* This hook is called before each request to the event engine backend */
            preRequestHook: undefined,
            /* This hook is called after each successful request to the event engine backend */
            postRequestHook: undefined,
        }
    };
})(window);
