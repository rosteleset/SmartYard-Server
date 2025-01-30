import { AKUVOX, BEWARD_DKS, BEWARD_DS, QTECH } from "../constants.js";
import { getAkuvoxMetrics, getBewardMetrics, getQtechMetrics } from "../metrics/index.js";

const modelMetrics = {
    [BEWARD_DKS]: getBewardMetrics,
    [BEWARD_DS]: getBewardMetrics,
    [QTECH]: getQtechMetrics,
    [AKUVOX]: getAkuvoxMetrics,
}
export const getMetrics = async ({ url, username, password, model }) => {
    const fetchMetrics = modelMetrics[model];
    if (!fetchMetrics){
        throw new Error(`Unsupported model: ${model}`);
    }
    return await fetchMetrics(url, username, password);
}