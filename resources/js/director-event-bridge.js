class DirectorEventBridge {
    constructor({ directorEventsUrl, csrfToken, liveClient, debug = false }) {
        this.directorEventsUrl = directorEventsUrl;
        this.csrfToken = csrfToken;
        this.liveClient = liveClient;
        this.debug = debug;
        this._pendingRequests = new Map();
        this._processedCount = 0;
        this._lastResult = null;
    }

    async processToolCall(call) {
        if (!call?.name) return null;

        if (call.name === 'request_roleplay_end') {
            return this._handleUnexpectedTool(call);
        }

        if (call.name !== 'report_roleplay_event') return null;

        const payload = this._buildPayload(call);
        const key = this._requestKey(payload);

        if (this._pendingRequests.has(key)) {
            return this._pendingRequests.get(key);
        }

        const promise = this._sendEvent(payload);
        this._pendingRequests.set(key, promise);

        try {
            const result = await promise;
            this._processedCount++;
            this._lastResult = result;

            const toolResponse = {
                accepted: result.accepted ?? false,
            };

            if (this.liveClient?.isReady()) {
                this.liveClient.sendToolResponse(call.name, toolResponse);
            }

            return result;
        } finally {
            this._pendingRequests.delete(key);
        }
    }

    _handleUnexpectedTool(call) {
        const response = { accepted: false, reason: 'not_yet_implemented' };

        if (this.liveClient?.isReady()) {
            this.liveClient.sendToolResponse(call.name, response);
        }

        return response;
    }

    _buildPayload(call) {
        const args = call.args || {};
        return {
            event_type: String(args.event_type || '').trim(),
            severity: args.severity || null,
            topic: args.topic || null,
            related_objection_key: args.related_objection_key || null,
            hidden_information_key: args.hidden_information_key || null,
            short_internal_reason: args.short_internal_reason || null,
        };
    }

    _requestKey(payload) {
        return `${payload.event_type}|${payload.topic}|${payload.severity}`;
    }

    async _sendEvent(payload) {
        try {
            const response = await fetch(this.directorEventsUrl, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': this.csrfToken,
                },
                body: JSON.stringify(payload),
            });

            if (!response.ok) {
                return { accepted: false, rejection_reason: 'Endpoint error' };
            }

            return await response.json();
        } catch (_) {
            return { accepted: false, rejection_reason: 'Network error' };
        }
    }

    getProcessedCount() {
        return this._processedCount;
    }

    getLastResult() {
        return this._lastResult;
    }

    reset() {
        this._pendingRequests.clear();
        this._processedCount = 0;
        this._lastResult = null;
    }
}

export { DirectorEventBridge };
