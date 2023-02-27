import {Router} from "express"
import {
  getSubscribers,
  getSubscriberByName,
  createSubscriber,
  updateSubscriber,
  deleteSubscriber,
} from "../../controllers/index.js"

const router = Router();

router.get("/", getSubscribers);

router.get("/:userName", getSubscriberByName);

router.post("/", createSubscriber);

router.put("/", updateSubscriber);

router.delete("/:userName", deleteSubscriber);

//TODO: add information about the client registration status

export default router;
