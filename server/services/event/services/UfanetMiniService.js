import { UfanetService } from './UfanetService.js';

/**
 * Class representing an event handler for Ufanet Secret Mini device.
 * @class
 * @augments UfanetService
 */
class UfanetMiniService extends UfanetService {
    /*
    Both Top and Mini emit `CALL_CLOSED` and `pickup 0` events, but only one of them
    can be used as a reliable call termination indicator depending on the device type.

    Mini:
    - `CALL_CLOSED` reliably indicates call completion.
    - `pickup 0` may be emitted multiple times during a single call or may not be emitted at all.

    Top:
    - `pickup 0` reliably indicates call completion.
    - `CALL_CLOSED` may be emitted multiple times during a single call or may not be emitted at all.

    Using a dedicated service is the simplest and most transparent solution.
    */
    isCallFinishedMessage(message) {
        return message.includes('CALL_CLOSED');
    }
}

export { UfanetMiniService };
