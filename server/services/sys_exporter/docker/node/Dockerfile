FROM node:22 as main
ENV TZ Europe/Moscow
ENV NODE_PATH /home/node/app/node_modules
WORKDIR /home/node/app
COPY ./package*.json /home/node/app
RUN npm i
COPY ./ .
