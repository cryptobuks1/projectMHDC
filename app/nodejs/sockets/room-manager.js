let models  = require('../models');
import jwt from 'jsonwebtoken';
import _ from 'lodash';
import config from './../config/environment';

var memberStorages = require('./../storages/member');
var modelStorages = require('./../storages/model');
let Redis = require('../components/Redis');
var http = require('../components/Http');


let PRIVATE_CACHE_PREFIX = 'PRIVATE_';
var rooms = {};
var clients = [];
var moment = require('moment');
var apiUrl = config.apiUrl;

export function register(socket,socketio) {
  socket.on('create-room', function(data, fn){
      var token = socket.handshake.query.token;
      http.post(apiUrl+'chat/create-group-chat', data, {
          "Authorization" :"Bearer " + token
      }).then(function (res) {
          var data = res.data;
          var arrUser = res.listUserJoinGroup;
          arrUser.forEach(function(element) {
              if(element != res.userId) {
                socketio.to("roomself_"+element).emit('request-joinroom', data);
              }
          });
          var typechat = res.typechat;
          var  arrUserDefault = res.arrUserDefault;
          if( typechat == 2) {
            arrUserDefault.forEach(function(element) {
                if(element != res.userId) {
                  socketio.to("roomself_"+element).emit('request-joinroom', data);
                }
            });
          }
          else {
            arrUserDefault.forEach(function(element) {
                if(element != res.userId) {
                  socketio.to("roomself_"+element).emit('create-room', data);
                }
            });
          }
          //socket.broadcast.to(data.threadId).emit('create-room', data);
          return socketio.to(socket.id).emit('createRoomSuccess', data);

          //return socket.emit('createRoomSuccess', data);
      }).catch(function (err) {
          return socket.emit('_error', {
                event: 'create-room',
                msg: err.msg || 'Lỗi hệ thống',
                data: data
            });
      });
  });

  socket.on('accept-joinroom', function(data){
    var token = socket.handshake.query.token;
      http.post(apiUrl+'chat/accept-joinroom', data, {
          "Authorization" :"Bearer " + token
      }).then(function (res) {
          //console.log("data-return accept");
          //console.log(res.data);
          socket.broadcast.to(res.groupChatId).emit('accept-joinroom', res.data);
          return socketio.to(socket.id).emit('acceptJoinRoomSuccess', res.data);
          //return socket.emit('createRoomSuccess', data);
      }).catch(function (err) {
          return socket.emit('_error', {
                event: 'accept-joinroom',
                msg: err.msg || 'Lỗi hệ thống',
                data: err
            });
      });
  });

  socket.on('resend-requestjoin', function(data){
    var token = socket.handshake.query.token;
      http.post(apiUrl+'chat/resend-requestjoin', data, {
          "Authorization" :"Bearer " + token
      }).then(function (res) {
          socketio.to("roomself_"+res.userId).emit('request-joinroom', res.data);
          return socketio.to(socket.id).emit('resend-requestjoin', res.data);

      }).catch(function (err) {
          return socket.emit('_error', {
                event: 'resend-requestjoin',
                msg: err.msg || 'Lỗi hệ thống',
                data: err
            });
      });
  });


  socket.on('set-name-conversation', function(data){
    var token = socket.handshake.query.token;
      http.post(apiUrl+'chat/set-name-conversation', data, {
          "Authorization" :"Bearer " + token
      }).then(function (res) {
          //console.log("gia tri set conversation");
          //console.log(res);
          var listUser = res.listUser;
          listUser.forEach(function(element) {
              if(element != res.userId) {
                socketio.to("roomself_"+element).emit('set-name-conversation', res.data);
              }
          });
          return socketio.to("roomself_"+res.userId).emit('set-name-conversation', res.data);

      }).catch(function (err) {
          return socket.emit('_error', {
                event: 'set-name-conversation',
                msg: err.msg || 'Lỗi hệ thống',
                data: err
            });
      });
  });

  socket.on('delete-setname-conversation', function(data){
    var token = socket.handshake.query.token;
      http.post(apiUrl+'chat/delete-setname-conversation', data, {
          "Authorization" :"Bearer " + token
      }).then(function (res) {
          console.log("gia tri delete conversation");
          console.log(res);
          var listUser = res.listUser;
          listUser.forEach(function(element) {
              if(element != res.userId) {
                socketio.to("roomself_"+element).emit('delete-setname-conversation', res.data);
              }
          });
          return socketio.to("roomself_"+res.userId).emit('delete-setname-conversation', res.data);

      }).catch(function (err) {
          return socket.emit('_error', {
                event: 'delete-setname-conversation',
                msg: err.msg || 'Lỗi hệ thống',
                data: err
            });
      });
  });


  socket.on('update-data-secret', function(data){
       socketio.to("roomself_"+data.userId).emit('update-data-secret',data);
  });


  socket.on('remove-joinroom', function(data){
    var token = socket.handshake.query.token;
      http.post(apiUrl+'chat/accept-joinroom', data, {
          "Authorization" :"Bearer " + token
      }).then(function (res) {
          socket.broadcast.to(res.groupChatId).emit('remove-joinroom', res.data);
          return socketio.to(socket.id).emit('removeJoinRoomSuccess', res.data);
          //return socket.emit('createRoomSuccess', data);
      }).catch(function (err) {
          return socket.emit('_error', {
                event: 'remove-joinroom',
                msg: err.msg || 'Lỗi hệ thống',
                data: err
          });
      });
  });

  socket.on('adduser-togroup', function(data){
      var token = socket.handshake.query.token;
      http.post(apiUrl+'chat/add-user-togroup', data, {
          "Authorization" :"Bearer " + token
      }).then(function (res) {
          //console.log(res);
          var data = res.data;
          var arrUser = res.listUserJoinGroup;
          arrUser.forEach(function(element) {
              if(element != res.userId) {
                socketio.to("roomself_"+element).emit('request-joinroom', data);
              }
          });
          return socketio.to(socket.id).emit('addUserToGroupSucess', data);

          //return socket.emit('createRoomSuccess', data);
      }).catch(function (err) {
          return socket.emit('_error', {
                event: 'adduser-togroup',
                msg: err.msg || 'Lỗi hệ thống',
                data: data
            });
      });
  });

  socket.on('typing-message', function(data){
    socket.broadcast.to(data.threadId).emit('listen-typing', data);
  });

  socket.on('end-typing', function(data){
    socket.broadcast.to(data.threadId).emit('update-message', data);
  });


  socket.on('update-group', function(data){
      var token = socket.handshake.query.token;
      http.post(apiUrl+'chat/update-group', data, {
          "Authorization" :"Bearer " + token
      }).then(function (res) {
          var data = res.data;
          socket.broadcast.to(data.threadId).emit('update-group', data);
          return socketio.to(socket.id).emit('update-group', data);
          //return socket.emit('createRoomSuccess', data);
      }).catch(function (err) {
          return socket.emit('_error', {
                event: 'update-group',
                msg: err.msg || 'Lỗi hệ thống',
                data: data
            });
      });
  });

  socket.on('block-message', function(data){
     var token = socket.handshake.query.token;
      http.post(apiUrl+'chat/block-message', data, {
          "Authorization" :"Bearer " + token
      }).then(function (res) {
          var data = res.data;
          //socket.broadcast.to(res.threadId).emit('block-message', data);
          socketio.to("roomself_"+res.userBlocked).emit('block-message', data);
          return socketio.to("roomself_"+res.userId).emit('blockMessageSuccess', data);
          //return socketio.to(socket.id).emit('blockMessageSuccess', data);
          //return socket.emit('createRoomSuccess', data);
      }).catch(function (err) {
          return socket.emit('_error', {
                event: 'block-message',
                msg: err.msg || 'Lỗi hệ thống',
                data: data
            });
      });
  });

  socket.on('block-notify', function(data){
     var token = socket.handshake.query.token;
      http.post(apiUrl+'chat/block-notify', data, {
          "Authorization" :"Bearer " + token
      }).then(function (res) {
          var data = res.data;
          //socket.broadcast.to(res.threadId).emit('block-notify', data);
          socketio.to("roomself_"+res.userBlocked).emit('block-notify', data);
          return socketio.to(socket.id).emit('blockNotifySuccess', data);

      }).catch(function (err) {
          return socket.emit('_error', {
                event: 'block-notify',
                msg: err.msg || 'Lỗi hệ thống',
                data: data
            });
      });
  });

  socket.on('block-notify-group', function(data){
     var token = socket.handshake.query.token;
      http.post(apiUrl+'chat/block-notify-group', data, {
          "Authorization" :"Bearer " + token
      }).then(function (res) {
          var data = res.data;
          return socketio.to(socket.id).emit('blockNotifyGroupSuccess', data);

      }).catch(function (err) {
          return socket.emit('_error', {
                event: 'block-notify-group',
                msg: err.msg || 'Lỗi hệ thống',
                data: data
            });
      });
  });


  socket.on('unblock-message', function(data){
      var token = socket.handshake.query.token;
      http.post(apiUrl+'chat/unblock-message', data, {
          "Authorization" :"Bearer " + token
      }).then(function (res) {
          var data = res.data;
          //socket.broadcast.to(res.threadId).emit('unblock-message', data);
          socketio.to("roomself_"+res.userBlocked).emit('unblock-message', data);
          return socketio.to("roomself_"+res.userId).emit('unBlockMessageSuccess', data);
          //return socketio.to(socket.id).emit('unBlockMessageSuccess', data);
          //return socket.emit('createRoomSuccess', data);
      }).catch(function (err) {
          return socket.emit('_error', {
                event: 'unblock-message',
                msg: err.msg || 'Lỗi hệ thống',
                data: data
            });
      });
  });

  socket.on('unblock-notify', function(data){
      var token = socket.handshake.query.token;
      http.post(apiUrl+'chat/unblock-notify', data, {
          "Authorization" :"Bearer " + token
      }).then(function (res) {
          var data = res.data;
          //socket.broadcast.to(res.threadId).emit('unblock-notify', data);
          socketio.to("roomself_"+res.userBlocked).emit('unblock-notify', data);
          return socketio.to(socket.id).emit('unBlockNotifySuccess', data);
          //return socket.emit('createRoomSuccess', data);
      }).catch(function (err) {
          return socket.emit('_error', {
                event: 'unblock-notify',
                msg: err.msg || 'Lỗi hệ thống',
                data: data
            });
      });
  });

  socket.on('unblock-notify-group', function(data){
      var token = socket.handshake.query.token;
      http.post(apiUrl+'chat/unblock-notify-group', data, {
          "Authorization" :"Bearer " + token
      }).then(function (res) {
          var data = res.data;
          return socketio.to(socket.id).emit('unBlockNotifyGroupSuccess', data);
          //return socket.emit('createRoomSuccess', data);
      }).catch(function (err) {
          return socket.emit('_error', {
                event: 'unblock-notify-group',
                msg: err.msg || 'Lỗi hệ thống',
                data: data
            });
      });
  });


  function userExistsInRoom(arr,userId) {
      return arr.some(function(el) {
        return el.userId === userId;
      });
  }

  socket.on('join-room', function(data, fn){
    socket.threadId = data.roomId || null;
    data.userData = socket.user || {};
    var roomId = data.roomId;
    var userId = data.userData.id;
    var type = data.type;
    socket.chatType = data.type;
    //var findRoom = _.findKey(firstAvailableRooms, {model: modelId});
    var userInGroup = (memberStorages.get(data.roomId));
    var exist = userExistsInRoom(userInGroup, socket.user.userId);
    if(!exist) {
        if(socket.user){
          memberStorages.add(data.roomId, socket.user);
        }
        socket.roomId = data.roomId;
        socket.join(roomId);
        socket.user.groupChatId = roomId;
        //socketio.to(socket.id).emit('join-room-user', socket.user);
        //return socket.broadcast.to(data.roomId).emit('join-room', socket.user);
        var token = socket.handshake.query.token;
        http.post(apiUrl+'chat/detail-userjoingroup', {userId: socket.user.userId, groupChatId: roomId}, {
              "Authorization" :"Bearer " + token
          }).then(function (res) {

            socketio.to(socket.id).emit('join-room-user', res.data);
            return socket.broadcast.to(data.roomId).emit('join-room',  res.data);

          }).catch(function (err) {
              return socket.emit('_error', {
                    event: 'chat/detail-userjoingroup',
                    msg: err.msg || 'Lỗi hệ thống',
                    data: err
              });
          });


    }
    else {
       socket.user.groupChatId = roomId;
       //socketio.to(socket.id).emit('join-room-user', socket.user );
       var token = socket.handshake.query.token;
        http.post(apiUrl+'chat/detail-userjoingroup', {userId: socket.user.userId, groupChatId: roomId}, {
              "Authorization" :"Bearer " + token
          }).then(function (res) {
              socketio.to(socket.id).emit('join-room-user', res.data );
          }).catch(function (err) {
              return socket.emit('_error', {
                    event: 'chat/detail-userjoingroup',
                    msg: err.msg || 'Lỗi hệ thống',
                    data: err
              });
          });
    }
  });


  socket.on('get-time-online', function(data, fn){
      var arrUser = modelStorages.getListModelUserId();
      var index = arrUser.indexOf(data.userId);
      if(index == -1) {
          var token = socket.handshake.query.token;
          http.post(apiUrl+'chat/get-timeoff-user', {userId: data.userId}, {
              "Authorization" :"Bearer " + token
          }).then(function (res) {
              socketio.to(socket.id).emit('get-time-online', { time: res.data } );
          }).catch(function (err) {
              return socket.emit('_error', {
                    event: 'get-timeoff-user',
                    msg: err.msg || 'Lỗi hệ thống',
                    data: err
              });
          });
      }
      else {
        socketio.to(socket.id).emit('get-time-online', { time: "Đang online" } );
      }

  });




  socket.on('check-online', function(data, fn){
      /*var clients = socketio.sockets.adapter.rooms[data.roomId];
      var value = 0;
      if(clients != null) {
        console.log(clients);
        if(clients.length >= 2) {
          value = 1;
        }
      }

      socketio.to(socket.id).emit('check-online', value );*/
      var arrUser = modelStorages.getListModelUserId();
     // console.log(arrUser);
      var index = arrUser.indexOf(data.userId);
      if(index == -1) {
        if (fn && _.isFunction(fn)) {
          fn({ online: false });
        }
        socketio.to(socket.id).emit('check-online', { online: false } );
      }
      else {
        if (fn && _.isFunction(fn)) {
          fn({ online:true });
        }
         socketio.to(socket.id).emit('check-online', { online: true } );
      }


  });





  /**
  * create private room or get current thread for the private message chage
  */
  socket.on('join-private-room', function(data, fn) {
    //restriction, for logged in user only
    if (!socket.user) { return; }

    var token = socket.handshake.query.token;
    http.post(apiUrl+'chat/create-private-chat', data, {
        "Authorization" :"Bearer " + token
    }).then(function (res) {
      //console.log(res);
      var thread = res.data.thread;
      // console.log('room: ',thread.groupChatId);
      //join this socket to private room
      socket.join(thread.groupChatId);
      /*if (fn && _.isFunction(fn)) {
        fn({ id: thread.id });
      }
      */
      //console.log('join private chat ' + thread.groupChatId);
      //socket.broadcast.to(thread.id).emit('join-room', socket.user);
      socket.user.groupChatId = thread.groupChatId;

      //socketio.sockets.in(thread.id).emit('join-room-private',  socket.user);
      //socketio.to("roomself_"+res.data.ownerId).emit('join-room-private', socket.user);
      socketio.to(socket.id).emit('join-room-private', thread );
      return socketio.to("roomself_"+res.data.memberId).emit('invited-room', thread);

    }).catch(function (err) {
        //console.log(err);
        return socket.emit('_error', {
              event: 'join-private-room',
              msg: err.msg || 'Lỗi hệ thống',
              data: err.msg
          });
    });

  });


  socket.on('join-secret-room', function(data, fn) {
    //restriction, for logged in user only
    //console.log("join secret ");
    //console.log(data);
    if (!socket.user) { return; }

    var token = socket.handshake.query.token;
    http.post(apiUrl+'chat/create-secret-chat', data, {
        "Authorization" :"Bearer " + token
    }).then(function (res) {
      var thread = res.data.thread;
      socket.join(thread.groupChatId);
      socket.user.groupChatId = thread.groupChatId;
      socketio.to(socket.id).emit('join-secret-private', thread );
      return socketio.to("roomself_"+res.data.memberId).emit('invited-room', thread);

    }).catch(function (err) {
        return socket.emit('_error', {
              event: 'join-secret-room',
              msg: err.msg || 'Lỗi hệ thống',
              data: err.msg
          });
    });

  });

  socket.on('disconnect', function() {
    try {
      if (socket.user) {
        //console.log("Có user disconect ne!");
        socket.broadcast.to(socket.user.groupChatId).emit('leave-room', socket.user);
        memberStorages.remove(socket.user);
        var token = socket.handshake.query.token;
        http.post(apiUrl+'chat/update-timeoff', {userId: socket.user.id}, {
            "Authorization" :"Bearer " + token
        }).then(function (res) {
            //console.log(res);
        }).catch(function (err) {
            return socket.emit('_error', {
                  event: 'update-timeoffline',
                  msg: err.msg || 'Lỗi hệ thống',
                  data: err
              });
        });
        //modelStorages.remove(socket);
      }
    } catch(err) {
      //console.log(err);
      // err
    }

    socket.log('DISCONNECTED IN ROOM');
  });

  socket.on('online-members', function(roomId, fn) {
    var members = memberStorages.get(roomId);
    socket.emit('online-members', {members});
  });

  socket.on('model-leave-room', function(){
    //console.log('model leave room');
    socket.emit('disconnect');
  });
  //event when member is missing tokens. break private chat.
  socket.on('member-missing-tokens', function(chatType){
    socket.broadcast.to(socket.threadId).emit('member-missing-tokens', chatType);
  });

  //model receive tokens from member
  socket.on('model-receive-info', function(data){
    socket.broadcast.emit('model-receive-info', data);
  });

  socket.on('delete-messagechat', function(data, fn) {
      var token = socket.handshake.query.token;
      http.post(apiUrl+'chat/delete-message', data, {
          "Authorization" :"Bearer " + token
      }).then(function (res) {
          var data = res.msg;
          if(data == 'failed') {
            //return socketio.to("roomself_"+res.userId).emit('delete-message', res.data);
            return socketio.to(socket.id).emit('delete-message', res.data);
          }
          else {
            var arrUser = res.arrUser;
            arrUser.forEach(function(element) {
                if(element != res.userId) {
                  socketio.to("roomself_"+element).emit('delete-message', res.data);
                }
            });
            return socketio.to("roomself_"+res.userId).emit('deleteMessageSuccess', res.data);
          }


      }).catch(function (err) {
          return socket.emit('_error', {
                event: 'delete-message',
                msg: err.msg || 'Lỗi hệ thống',
                data: data
            });
      });

  });


  socket.on('delete-image-inmulti', function(data) {
      var token = socket.handshake.query.token;
      http.post(apiUrl+'chat/delete-image-inmulti', data, {
          "Authorization" :"Bearer " + token
      }).then(function (res) {
          if(typeof res.arrUser != 'undefined') {
              var arrUser = res.arrUser;
              arrUser.forEach(function(element) {
                  socketio.to("roomself_"+element).emit('delete-image-inmulti', res.data);
              });
          }
          else {
              socketio.to(socket.id).emit('delete-image-inmulti', res.data);
          }

      }).catch(function (err) {
          return socket.emit('_error', {
                event: 'delete-image-inmulti',
                msg: err.msg || 'Lỗi hệ thống',
                data: data
            });
      });

  });


  socket.on('delete-chat-secret', function(data, fn) {
      var token = socket.handshake.query.token;
      http.post(apiUrl+'chat/delete-chat-secret', data, {
          "Authorization" :"Bearer " + token
      }).then(function (res) {
          var arrUser = res.arrUser;
          arrUser.forEach(function(element) {
              if(element != res.userId) {
                socketio.to("roomself_"+element).emit('delete-chat-secret', res.data);
              }
          });
          return socketio.to("roomself_"+res.userId).emit('deleteChatSecretSuccess', res.data);

      }).catch(function (err) {
          return socket.emit('_error', {
                event: 'delete-chat-secret',
                msg: err.msg || 'Lỗi hệ thống',
                data: data
            });
      });

  });


  socket.on('setstatus-message', function(data) {
      var token = socket.handshake.query.token;
      http.post(apiUrl+'chat/setstatus-message', data, {
          "Authorization" :"Bearer " + token
      }).then(function (res) {
          if(res.type == 'private' || res.type == 'secret') {
             return socketio.to("roomself_"+res.idSend).emit('setstatus-message', res.data);
          }
          else {
            if(res.statusRead != 0) {
                return socketio.to("roomself_"+res.ownerMessage).emit('finished-readmessage', res.data);
            }
          }

      }).catch(function (err) {
          return socket.emit('_error', {
                event: 'setstatus-message',
                msg: err.msg || 'Lỗi hệ thống',
                data: data
            });
      });
  });


  socket.on('delete-useringroup', function(data) {

      var token = socket.handshake.query.token;
      http.post(apiUrl+'chat/delete-useringroup', data, {
          "Authorization" :"Bearer " + token
      }).then(function (res) {

      }).catch(function (data) {
          if( data.status == 200) {
            //socketio.sockets.in(data.threadId).emit('leave-room',  socket.user);
            //socketio.to(socket.id).emit('leave-room-user', socket.user);
            //socket.broadcast.to(data.threadId).emit('leave-room', socket.user);
            //socketio.sockets.in(data.threadId).emit('leave-room',  data.infoUserDelete);
            var arrUser = data.arrUser;
            arrUser.forEach(function(element) {
                socketio.to("roomself_"+element).emit('leave-room', data.infoUserDelete);
            });
            memberStorages.remove(socket.user);
            modelStorages.remove(socket);
          }
          else {
            return socket.emit('_error', {
                event: 'delete-useringroup',
                msg: err.msg || 'Xóa không thành công ',
                data: data
            });
          }

      });

  });


  socket.on('delete-conversation', function(data, fn) {
      //console.log(socket.user);
      var token = socket.handshake.query.token;
      http.post(apiUrl+'chat/delete-conversation', data, {
          "Authorization" :"Bearer " + token
      }).then(function (res) {
          var arrUser = res.data;
          //console.log(arrUser);
          arrUser.forEach(function(element) {
              data.userId = element;
              data.groupChatId = socket.user.groupChatId;
              socketio.to("roomself_"+element).emit('deleteConversationSuccess', data);
              socketio.to("roomself_"+element).emit('count-message', res.arrCountMessage[element]);
          });
          return true;
          //socket.broadcast.to(socket.user.groupChatId).emit('delete-conversation', data);
          //return socketio.to(socket.id).emit('deleteConversationSuccess', data);
          //return socket.emit('deleteConversationSuccess', data);
      }).catch(function (err) {
          return socket.emit('_error', {
                event: 'delete-conversation',
                msg: err.msg || 'Lỗi hệ thống',
                data: data
            });
      });
  });


  socket.on('update-background', function(data) {
      var token = socket.handshake.query.token;
      http.post(apiUrl+'chat/update-background', data, {
          "Authorization" :"Bearer " + token
      }).then(function (res) {
            if(res.side == '1') {
              return socketio.to("roomself_"+res.userId).emit('update-background', res.data);
                //return socketio.to(socket.id).emit('update-background', res.data);
            }
            else {
              var arrUser = res.listUser;
              arrUser.forEach(function(element) {
                  socketio.to("roomself_"+element.userId).emit('update-background', res.data);
              });
              return true;
            }

      }).catch(function (err) {
          //console.log(err);
          return socket.emit('_error', {
                event: 'update-background',
                msg: err.msg || 'Lỗi hệ thống',
                data: err
            });
      });
  });


  socket.on('addusergroup-toadmin', function(data) {
      var token = socket.handshake.query.token;
      http.post(apiUrl+'chat/adduser-toadmin', data, {
          "Authorization" :"Bearer " + token
      }).then(function (res) {
            var arrUser = res.listUser;
            arrUser.forEach(function(element) {
                socketio.to("roomself_"+element).emit('addusergroup-toadmin', res.data);
            });

      }).catch(function (err) {
          return socket.emit('_error', {
                event: 'addusergroup-toadmin',
                msg: err.msg || 'Lỗi hệ thống',
                data: err
            });
      });
  });

  socket.on('deleteuseradmin-ingroup', function(data) {
      var token = socket.handshake.query.token;
      http.post(apiUrl+'chat/deleteuseradmin-ingroup', data, {
          "Authorization" :"Bearer " + token
      }).then(function (res) {
            var arrUser = res.listUser;
            arrUser.forEach(function(element) {
                socketio.to("roomself_"+element).emit('deleteuseradmin-ingroup', res.data);
            });
      }).catch(function (err) {
          return socket.emit('_error', {
                event: 'deleteuseradmin-ingroup',
                msg: err.msg || 'Lỗi hệ thống',
                data: err
            });
      });
  });

  socket.on('count-message', function() {
      /*var token = socket.handshake.query.token;
      http.post(apiUrl+'chat/countmessage-notread', {}, {
          "Authorization" :"Bearer " + token
      }).then(function (res) {
         console.log(res);
          //socketio.to(socket.id).emit('count-message', res.data);
          socketio.to("roomself_"+res.userId).emit('count-message', res.data);
      }).catch(function (err) {
          console.log(err);
          return socket.emit('_error', {
                event: 'countmessage-notread',
                msg: err.msg || 'Lỗi hệ thống',
                data: err
            });
      });*/
      var token = socket.handshake.query.token;
       http.post(apiUrl+'chat/countmessage-notread', {
        }, { "Authorization" :"Bearer " + token })
        .then(function (res) {
            return socketio.to("roomself_"+res.userId).emit('count-message', res.data);
        })
        .catch(function (res) {
          //console.log(res);
          if(res.msg == 'Success') {
            return socketio.to("roomself_"+res.userId).emit('count-message', res.data);
          }
          else{
            return socket.emit('_error', {
                event: 'countmessage-notread',
                msg: res.msg || 'Lỗi hệ thống',
                data: res
            });
          }

        });

  });

  socket.on('count-invite', function(data) {
       var token = socket.handshake.query.token;
       http.post(apiUrl+'chat/countinvite-notread', {
        }, { "Authorization" :"Bearer " + token })
        .then(function (res) {
            return socketio.to("roomself_"+res.userId).emit('count-invite', res.data);
        })
        .catch(function (res) {
          if(res.msg == 'Success') {
            return socketio.to("roomself_"+res.userId).emit('count-invite', res.data);
          }
          else{
            return socket.emit('_error', {
                event: 'countinvite-notread',
                msg: res.msg || 'Lỗi hệ thống',
                data: res
            });
          }

        });

    });


  socket.on('set-message-emoij', function(data) {
       var token = socket.handshake.query.token;
       http.post(apiUrl+'chat/set-message-emoij', data, { "Authorization" :"Bearer " + token })
        .then(function (res) {
            var arrUser = res.listUser;
            arrUser.forEach(function(element) {
                socketio.to("roomself_"+element.userId).emit('message-emoij', res.arrReturn[element.userId]);
            });
        })
        .catch(function (err) {
            return socket.emit('_error', {
                event: 'set-message-emoij',
                msg: err.msg || 'Lỗi hệ thống',
                data: err
            });

        });

    });


  socket.on('stop-share-location', function(data) {
       console.log("debug_ share location ");
       var token = socket.handshake.query.token;
       http.post(apiUrl+'chat/stop-share-location', data, { "Authorization" :"Bearer " + token })
        .then(function (res) {
            console.log(res);
            var arrUser = res.listUser;
            arrUser.forEach(function(element) {
                socketio.to("roomself_"+element).emit('stop-share-location', res.data);
            });
        })
        .catch(function (err) {
            console.log(err);
            return socket.emit('_error', {
                event: 'stop-share-location',
                msg: err.msg || 'Lỗi hệ thống',
                data: err
            });

        });

    });



  socket.on('forward-message', function(data) {
       var token = socket.handshake.query.token;
       http.post(apiUrl+'chat/forward-message', data, { "Authorization" :"Bearer " + token })
        .then(function (res) {
            //console.log(res);
            var arrUser = res.arrUser;
            if(arrUser.length > 0) {
                var arrCountMessageUser = res.arrCountMessageUser;
                var arrUserData = res.arrUserData;
                arrUser.forEach(function(element) {
                    socketio.to("roomself_"+element).emit('new-chat-message', arrUserData[element]);
                    socketio.to("roomself_"+element).emit('count-message', arrCountMessageUser[element]);
              });
            }

            var arrUserGroup = res.arrUserGroup;
            if(arrUserGroup.length > 0) {
                var arrCountMessageGroup = res.arrCountMessageGroup;
                var arrGroupData = res.arrGroupData;
                arrUserGroup.forEach(function(element) {
                    socketio.to("roomself_"+element).emit('new-chat-message', arrGroupData[element]);
                    socketio.to("roomself_"+element).emit('count-message', arrCountMessageGroup[element]);
              });
            }

            var arrUserDefault = res.arrUserDefault;
            if(arrUserDefault.length > 0) {
                var arrCountMessageDefault = res.arrCountMessageDefault;
                var dataDefault = res.dataDefault;
                arrUserDefault.forEach(function(element) {
                    socketio.to("roomself_"+element).emit('new-chat-message', dataDefault[element]);
                    socketio.to("roomself_"+element).emit('count-message', arrCountMessageDefault[element]);
              });
            }
            socketio.to("roomself_"+res.userForward).emit('forwardSuccess', 'Forward Successfully');


        })
        .catch(function (res) {
            //console.log("co loi");
            //console.log(res);
            return socket.emit('_error', {
                event: 'forward-message',
                msg: res.msg || 'Lỗi hệ thống',
                data: res
            });

        });

  });

  socket.on('share-product', function(data) {
      var token = socket.handshake.query.token;
      var caption = "";
      var timeDelete = -1;
      var message_repeat = 0;
      var content_repeat = "";
      var time_repeat = 0;

      if(typeof data.caption != 'undefined') {
        caption = data.caption;
      }
      if(typeof data.timeDelete != 'undefined') {
        timeDelete = data.timeDelete;
      }
      if(typeof data.message_repeat != 'undefined') {
        message_repeat = data.message_repeat;
      }

      if(typeof data.content_repeat != 'undefined') {
        content_repeat = data.content_repeat;
      }

      if(typeof data.time_repeat != 'undefined') {
        time_repeat = data.time_repeat;
      }

      var message = {
        type: data.type,
        ownerId: socket.user.id,
        threadId: data.groupChatId,
        text: "",
        typedata: "product",
        messageId: 0,
        width: 0,
        height: 0,
        size: "",
        listImage: "",
        productId: data.productId,
        caption:caption,
        timeDelete: timeDelete,
        message_repeat: message_repeat,
        content_repeat: content_repeat,
        time_repeat:time_repeat,
      };

      http.post(apiUrl+'chat/send-message', message, {
          "Authorization" :"Bearer " + token
      }).then(function (res) {
          socketio.to("roomself_"+res.userSend).emit('shareProductSuccess', 'Share product Successfully');
          console.log("check - share - product");
          var arrUser = res.arrUser;
          //console.log(arrUser);
          if(res.condGroup == 1) {
            arrUser.forEach(function(element) {
                var datareturn = res.data;
                if(datareturn['parentMessId'] != 0) {
                  if( datareturn['ownerId'] != element) {
                   datareturn['status_repeat'] = 1;
                  }
                  else {
                    datareturn['status_repeat'] = 0;
                  }
                }
                else {
                  if( (datareturn['parentMessId']) == 0 && (datareturn['ownerId'] != element) && ( datareturn['message_repeat'] == 1) ) {
                      datareturn['status_repeat'] = 1;
                  }
                  else {
                    datareturn['status_repeat'] = 0;
                  }
                }
                socketio.to("roomself_"+element).emit('new-chat-message', datareturn);
                socketio.to("roomself_"+element).emit('count-message', res.arrCountMessage[element]);
            });
            arrUser.forEach(function(element) {
                if( typeof res.dataDefault[element] != 'undefined') {
                  socketio.to("roomself_"+element).emit('new-chat-message', res.dataDefault[element]);
                  socketio.to("roomself_"+element).emit('count-message', parseInt(res.arrCountMessage[element]) + 1);
                }
            });
          }
          else {
              arrUser.forEach(function(element) {
                  if( typeof res.dataDefault[element] != 'undefined') {
                    socketio.to("roomself_"+element).emit('new-chat-message', res.dataDefault[element]);
                    socketio.to("roomself_"+element).emit('count-message', res.arrCountMessage[element]);
                  }
                  else {
                    var datareturn = res.data;
                    if(datareturn['parentMessId'] != 0) {
                      if( datareturn['ownerId'] != element) {
                       datareturn['status_repeat'] = 1;
                      }
                      else {
                        datareturn['status_repeat'] = 0;
                      }
                    }
                    else {
                      if( (datareturn['parentMessId']) == 0 && (datareturn['ownerId'] != element) && ( datareturn['message_repeat'] == 1) ) {
                          datareturn['status_repeat'] = 1;
                      }
                      else {
                        datareturn['status_repeat'] = 0;
                      }
                    }

                    socketio.to("roomself_"+element).emit('new-chat-message', datareturn);
                    socketio.to("roomself_"+element).emit('count-message', res.arrCountMessage[element]);
                  }
              });
          }


      }).catch(function (err) {
          return socket.emit('_error', {
                event: 'share-product',
                msg: err.msg || 'Lỗi hệ thống',
                data: err
            });
      });
  });




}