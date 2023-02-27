import { Router } from "express";
import {
  getAll,
  create,
  getByName,
  destroy,
  update,
} from "../../controllers/index.js";

const router = Router();

router.get("/", getAll);

router.get("/:userName", getByName);

router.post("/", create);

router.put("/", update);

router.delete("/:userName", destroy);

//TODO: add information about the client registration status

export default router;
