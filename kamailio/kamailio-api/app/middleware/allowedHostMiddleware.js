export const allowedHostMiddleware = ({ allowedHosts }) => {
  return (req, res, next) => {
    const ipAddress = req.ip.split(":")[req.ip.split(":").length - 1];

    if (allowedHosts.includes(ipAddress)) {
      return next();
    }

    return res.sendStatus(401);
  };
};
