import { UfanetService } from './UfanetService.js';

/**
 * Class representing an event handler for Ufanet Secret Mini device.
 * @class
 * @augments UfanetService
 */
class UfanetMiniService extends UfanetService {
    isCallFinishedMessage(message) {
        return message.includes('CALL_CLOSED');
    }
}

export { UfanetMiniService };
