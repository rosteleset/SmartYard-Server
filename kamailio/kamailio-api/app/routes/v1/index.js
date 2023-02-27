import {Router} from "express"
import subscribers from "./subscribers.js"

const router = Router();

router.use("/subscribers", subscribers);

export default router