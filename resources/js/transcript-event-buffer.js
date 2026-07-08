class TranscriptEventBuffer {
    constructor({ transcriptUrl, onSubmitted, onError, debug = false }) {
        this.transcriptUrl = transcriptUrl;
        this.onSubmitted = onSubmitted;
        this.onError = onError;
        this.debug = debug;

        this._sequence = 0;
        this._pendingPartial = { USER: null, AI: null };
        this._submittedSequences = new Set();
        this._submittedTexts = new Set();
        this._submitting = false;
        this._submitQueue = [];
        this._active = false;
    }

    start() {
        this._active = true;
        this._flushQueue();
    }

    stop() {
        this._active = false;
    }

    isActive() {
        return this._active;
    }

    receive(event) {
        if (!this._active) return;
        if (!event?.speaker || !event?.text) return;

        const speaker = event.speaker === 'AI' ? 'AI' : 'USER';
        const text = String(event.text).trim();
        if (!text) return;

        const isFinal = event.status === 'final';

        if (isFinal) {
            this._handleFinal(speaker, text);
        } else {
            this._pendingPartial[speaker] = { speaker, text };
        }
    }

    _handleFinal(speaker, text) {
        this._pendingPartial[speaker] = null;
        this._submitTurn(speaker, text, 'FINAL');
    }

    flush() {
        if (!this._active) return;

        ['USER', 'AI'].forEach((speaker) => {
            if (this._pendingPartial[speaker]) {
                const { text } = this._pendingPartial[speaker];
                this._pendingPartial[speaker] = null;
                this._submitTurn(speaker, text, 'FINAL');
            }
        });
    }

    _submitTurn(speaker, text, status) {
        const seq = this._sequence++;

        const dedupKey = `${seq}:${speaker}:${text}`;
        if (this._submittedSequences.has(seq)) return true;
        if (this._submittedTexts.has(dedupKey)) return true;

        this._submittedSequences.add(seq);
        this._submittedTexts.add(dedupKey);

        const payload = {
            sequence: seq,
            speaker,
            text,
            status,
            started_at: new Date().toISOString(),
            ended_at: new Date().toISOString(),
        };

        this._enqueueOrSubmit(payload);
    }

    _enqueueOrSubmit(payload) {
        if (this._submitting) {
            this._submitQueue.push(payload);
            return;
        }

        this._submitting = true;
        this._doSubmit(payload);
    }

    async _doSubmit(payload) {
        if (!this.transcriptUrl) {
            this._submitting = false;
            return;
        }

        try {
            const response = await fetch(this.transcriptUrl, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                },
                body: JSON.stringify(payload),
            });

            if (response.ok) {
                const data = await response.json();
                this.onSubmitted?.(data);
            } else {
                const body = await response.text();
                if (this.debug) {
                    this._log('submit rejected', response.status, body);
                }
                this.onError?.({ status: response.status, body });
            }
        } catch (err) {
            if (this.debug) {
                this._log('submit error', err);
            }
            this.onError?.({ error: err });
        }

        this._submitting = false;
        this._flushQueue();
    }

    _flushQueue() {
        if (!this._active) return;

        while (this._submitQueue.length > 0 && !this._submitting) {
            const next = this._submitQueue.shift();
            this._submitting = true;
            this._doSubmit(next);
            return;
        }
    }

    _log(...args) {
        if (this.debug && typeof console !== 'undefined') {
            console.debug('[TranscriptEventBuffer]', ...args);
        }
    }

    getCurrentSequence() {
        return this._sequence;
    }

    getPendingPartial(speaker) {
        return this._pendingPartial[speaker] || null;
    }
}

export { TranscriptEventBuffer };
